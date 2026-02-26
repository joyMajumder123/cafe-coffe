/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  Cafe-App  ·  k6 Load & Stress Test Suite
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  Usage:
 *    k6 run test.js                        # default (smoke)
 *    k6 run --env SCENARIO=load test.js    # sustained load
 *    k6 run --env SCENARIO=stress test.js  # stress / spike
 *    k6 run --env SCENARIO=soak test.js    # long endurance
 *
 *  Prerequisites:
 *    1. Install k6  →  https://k6.io/docs/get-started/installation/
 *    2. XAMPP (Apache + MySQL) must be running.
 *    3. A test customer account is auto-registered via setup().
 *
 *  What it measures:
 *    • Response time (p95, p99, avg, max)
 *    • Throughput (req/s)
 *    • Error rate per endpoint
 *    • Concurrent-user capacity
 *    • DB-heavy page resilience (dashboard, orders, menu)
 * ─────────────────────────────────────────────────────────────────────────────
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ── Configuration ───────────────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || 'http://localhost/cafe-app';
const SCENARIO = (__ENV.SCENARIO || 'smoke').toLowerCase();

const TEST_CUSTOMER = {
    name: 'Load Tester',
    email: `loadtest_${__VU || 0}_${Date.now()}@cafe.test`,
    password: 'Test1234!',
};

// ── Custom Metrics ──────────────────────────────────────────────────────────
const pageLoadTime     = new Trend('page_load_time', true);
const apiResponseTime  = new Trend('api_response_time', true);
const orderPlaceTime   = new Trend('order_place_time', true);
const loginTime        = new Trend('login_time', true);
const errorRate        = new Rate('errors');
const httpFailures     = new Counter('http_failures');
const orderSuccess     = new Counter('orders_placed');
const orderFail        = new Counter('orders_failed');

// ── Scenario Profiles ───────────────────────────────────────────────────────
const SCENARIOS = {
    // Quick sanity check — 3 users for 30 seconds
    smoke: {
        executor: 'constant-vus',
        vus: 3,
        duration: '30s',
    },
    // Sustained realistic load — ramp from 0 → 20 → 50 → 0
    load: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '1m',  target: 20 },
            { duration: '3m',  target: 20 },
            { duration: '1m',  target: 50 },
            { duration: '3m',  target: 50 },
            { duration: '1m',  target: 0 },
        ],
    },
    // Spike / stress test — push to 200 concurrent users
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 30 },
            { duration: '1m',  target: 100 },
            { duration: '30s', target: 100 },
            { duration: '30s', target: 200 },
            { duration: '1m',  target: 200 },
            { duration: '1m',  target: 0 },
        ],
    },
    // Long endurance run — 30 users for 15 minutes
    soak: {
        executor: 'constant-vus',
        vus: 30,
        duration: '15m',
    },
};

export const options = {
    scenarios: {
        default: SCENARIOS[SCENARIO] || SCENARIOS.smoke,
    },
    thresholds: {
        // Global HTTP thresholds
        http_req_duration:  ['p(95)<2000', 'p(99)<4000'],   // 95% < 2s, 99% < 4s
        http_req_failed:    ['rate<0.05'],                   // < 5% HTTP errors

        // Custom metric thresholds
        errors:             ['rate<0.1'],                    // < 10% logical errors
        page_load_time:     ['p(95)<3000'],                  // Full page < 3s at p95
        api_response_time:  ['p(95)<1500'],                  // API calls < 1.5s at p95
        order_place_time:   ['p(95)<2000'],                  // Order POST < 2s at p95
        login_time:         ['p(95)<1500'],                  // Login POST < 1.5s at p95
    },
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)', 'count'],
};

// ── Helpers ─────────────────────────────────────────────────────────────────

/** Extract a hidden input value (e.g. CSRF token) from HTML */
function extractHidden(html, name) {
    const re = new RegExp(
        '<input[^>]*name=["\']' + name + '["\'][^>]*value=["\']([^"\']*)["\']', 'i'
    );
    const m = re.exec(html);
    return m ? m[1] : '';
}

/** Random float between min and max */
function rand(min, max) {
    return min + Math.random() * (max - min);
}

/** Standard page-level checks */
function checkPage(res, label) {
    const passed = check(res, {
        [`${label} — status 200`]: (r) => r.status === 200,
        [`${label} — body not empty`]: (r) => r.body && r.body.length > 100,
        [`${label} — no PHP fatal`]: (r) =>
            !r.body.includes('Fatal error') &&
            !r.body.includes('Parse error'),
        [`${label} — no PHP warning`]: (r) =>
            !r.body.includes('Warning:'),
    });
    if (!passed) {
        httpFailures.add(1);
        errorRate.add(1);
    }
    return passed;
}

/** Log in as the test customer; returns a cookie jar for subsequent requests */
function loginAsCustomer(jar, email, password) {
    const loginPage = http.get(`${BASE_URL}/customer_login.php`, {
        jar,
        tags: { name: 'GET /customer_login.php' },
    });
    const csrf = extractHidden(loginPage.body, '_csrf_token');

    const res = http.post(
        `${BASE_URL}/customer_login.php`,
        { email, password, _csrf_token: csrf },
        { jar, redirects: 5, tags: { name: 'POST /customer_login.php' } }
    );
    loginTime.add(res.timings.duration);

    const ok = check(res, {
        'login — no error banner': (r) => !r.body.includes('alert-danger'),
    });
    if (!ok) { errorRate.add(1); }
    return ok;
}

// ── Setup: register a throw-away test account ───────────────────────────────
export function setup() {
    const email = `loadtest_${Date.now()}@cafe.test`;

    // Register
    const regPage = http.get(`${BASE_URL}/customer_register.php`);
    const csrf = extractHidden(regPage.body, '_csrf_token');
    const res = http.post(
        `${BASE_URL}/customer_register.php`,
        {
            name: TEST_CUSTOMER.name,
            email,
            phone: '9876543210',
            password: TEST_CUSTOMER.password,
            confirm_password: TEST_CUSTOMER.password,
            _csrf_token: csrf,
        },
        { redirects: 0 }
    );

    const ok = res.status === 302 || res.status === 200;
    if (!ok) {
        console.warn(`⚠ Registration returned ${res.status}. Tests may partially fail.`);
    }

    // Pick a real menu item from the live DB for order tests
    const menuPage = http.get(`${BASE_URL}/menulist.php`);
    const ids    = [];
    const names  = [];
    const prices = [];
    const idRe    = /data-id="(\d+)"/g;
    const nameRe  = /data-name="([^"]+)"/g;
    const priceRe = /data-price="([\d.]+)"/g;
    let m;
    while ((m = idRe.exec(menuPage.body))    !== null) ids.push(parseInt(m[1], 10));
    while ((m = nameRe.exec(menuPage.body))  !== null) names.push(m[1]);
    while ((m = priceRe.exec(menuPage.body)) !== null) prices.push(parseFloat(m[1]));

    const menuItems = ids.map((id, i) => ({
        id,
        name: names[i] || 'Item',
        price: prices[i] || 100,
    }));

    console.log(`✅ Setup done — ${menuItems.length} menu item(s) found, test email: ${email}`);

    return {
        email,
        password: TEST_CUSTOMER.password,
        menuItems: menuItems.length ? menuItems : [{ id: 1, name: 'Fallback', price: 100 }],
    };
}

// ── Default VU function (user journey dispatcher) ───────────────────────────
export default function (data) {
    // Blend of traffic patterns that mirror realistic usage:
    //   35%  anonymous browsing
    //   25%  authenticated browsing (profile, order history)
    //   20%  place an order
    //   20%  rapid API polling (simulates AJAX / mobile-app traffic)
    const roll = Math.random();

    if (roll < 0.35) {
        anonymousBrowsing();
    } else if (roll < 0.60) {
        authenticatedBrowsing(data);
    } else if (roll < 0.80) {
        placeOrder(data);
    } else {
        rapidApiPolling(data);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  Journey 1 — Anonymous Browsing  (35% of traffic)
//  Simulates a visitor clicking through public pages with think-time pauses.
// ─────────────────────────────────────────────────────────────────────────────
function anonymousBrowsing() {
    group('01 · Anonymous Browsing', function () {
        const pages = [
            { url: '/index.php',    label: 'Home' },
            { url: '/menulist.php', label: 'Menu' },
            { url: '/about.php',    label: 'About' },
            { url: '/contact.php',  label: 'Contact' },
            { url: '/gallery.php',  label: 'Gallery' },
            { url: '/chefs.php',    label: 'Chefs' },
        ];

        for (const page of pages) {
            const res = http.get(`${BASE_URL}${page.url}`, {
                tags: { name: `GET ${page.url}` },
            });
            pageLoadTime.add(res.timings.duration);
            checkPage(res, page.label);
            sleep(rand(0.8, 2.5));  // think time
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
//  Journey 2 — Authenticated Browsing  (25% of traffic)
//  Login → profile → order history → order status API → checkout page
// ─────────────────────────────────────────────────────────────────────────────
function authenticatedBrowsing(data) {
    group('02 · Authenticated Browsing', function () {
        const jar = http.cookieJar();
        const loggedIn = loginAsCustomer(jar, data.email, data.password);
        if (!loggedIn) return;

        // Profile
        let res = http.get(`${BASE_URL}/profile.php`, {
            jar, tags: { name: 'GET /profile.php' },
        });
        pageLoadTime.add(res.timings.duration);
        checkPage(res, 'Profile');
        sleep(rand(1, 2));

        // Order history (DB heavy — reads all customer orders)
        res = http.get(`${BASE_URL}/order_history.php`, {
            jar, tags: { name: 'GET /order_history.php' },
        });
        pageLoadTime.add(res.timings.duration);
        checkPage(res, 'Order history');
        sleep(rand(1, 2));

        // Order status JSON API
        res = http.get(`${BASE_URL}/order_status.php`, {
            jar, tags: { name: 'GET /order_status.php' },
        });
        apiResponseTime.add(res.timings.duration);
        check(res, {
            'order_status — 200': (r) => r.status === 200,
            'order_status — valid JSON': (r) => {
                try { JSON.parse(r.body); return true; } catch { return false; }
            },
        });
        sleep(rand(0.5, 1));

        // Checkout page
        res = http.get(`${BASE_URL}/checkout.php`, {
            jar, tags: { name: 'GET /checkout.php' },
        });
        pageLoadTime.add(res.timings.duration);
        checkPage(res, 'Checkout');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
//  Journey 3 — Place an Order  (20% of traffic)
//  Login → checkout → POST place_order.php with random cart
// ─────────────────────────────────────────────────────────────────────────────
function placeOrder(data) {
    group('03 · Place Order', function () {
        const jar = http.cookieJar();
        const loggedIn = loginAsCustomer(jar, data.email, data.password);
        if (!loggedIn) return;

        // Load checkout to grab CSRF token
        const checkoutRes = http.get(`${BASE_URL}/checkout.php`, {
            jar, tags: { name: 'GET /checkout.php (order flow)' },
        });
        const csrf = extractHidden(checkoutRes.body, '_csrf_token');

        // Build a random cart (1-3 items, each qty 1-4)
        const numItems = Math.min(
            Math.ceil(Math.random() * 3),
            data.menuItems.length
        );
        const cart = [];
        for (let i = 0; i < numItems; i++) {
            const item = data.menuItems[i % data.menuItems.length];
            cart.push({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: Math.ceil(Math.random() * 4),
            });
        }

        const res = http.post(
            `${BASE_URL}/place_order.php`,
            {
                customer_name: TEST_CUSTOMER.name,
                email: data.email,
                phone: '9876543210',
                address: `${Math.floor(Math.random() * 999)} Load Test Ave`,
                city: 'Testville',
                items: JSON.stringify(cart),
                delivery_charge: '2.50',
                payment_method: 'cash_on_delivery',
                _csrf_token: csrf,
            },
            { jar, tags: { name: 'POST /place_order.php' } }
        );

        orderPlaceTime.add(res.timings.duration);

        let body;
        try { body = JSON.parse(res.body); } catch { body = {}; }

        const passed = check(res, {
            'order — HTTP 200':    (r) => r.status === 200,
            'order — success':     () => body.success === true,
            'order — has order_id':() => typeof body.order_id === 'number' && body.order_id > 0,
        });

        if (passed) {
            orderSuccess.add(1);
        } else {
            orderFail.add(1);
            errorRate.add(1);
            if (body.message) {
                console.warn(`Order failed: ${body.message}`);
            }
        }

        sleep(rand(1, 3));
    });
}

// ─────────────────────────────────────────────────────────────────────────────
//  Journey 4 — Rapid API Polling  (20% of traffic)
//  Simulates mobile/SPA clients hammering endpoints repeatedly.
//  Tests DB under high concurrent read pressure.
// ─────────────────────────────────────────────────────────────────────────────
function rapidApiPolling(data) {
    group('04 · Rapid API Polling', function () {
        // Unauthenticated: hit menu + home rapidly (DB reads: menu_items, categories)
        for (let i = 0; i < 5; i++) {
            const page = i % 2 === 0 ? '/menulist.php' : '/index.php';
            const res = http.get(`${BASE_URL}${page}`, {
                tags: { name: `GET ${page} (rapid)` },
            });
            apiResponseTime.add(res.timings.duration);
            checkPage(res, `Rapid ${page}`);
            sleep(rand(0.05, 0.3));
        }

        // Authenticated: poll order_status like a live-tracking UI
        const jar = http.cookieJar();
        const loggedIn = loginAsCustomer(jar, data.email, data.password);
        if (!loggedIn) return;

        for (let i = 0; i < 8; i++) {
            const res = http.get(`${BASE_URL}/order_status.php`, {
                jar, tags: { name: 'GET /order_status.php (rapid poll)' },
            });
            apiResponseTime.add(res.timings.duration);
            check(res, {
                'rapid poll — 200': (r) => r.status === 200,
            }) || errorRate.add(1);
            sleep(rand(0.1, 0.4));  // aggressive polling interval
        }
    });
}

// ── Teardown ────────────────────────────────────────────────────────────────
export function teardown(data) {
    console.log('');
    console.log('╔═══════════════════════════════════════════════════╗');
    console.log('║          ☕  Cafe-App Load Test Complete          ║');
    console.log('╠═══════════════════════════════════════════════════╣');
    console.log(`║  Scenario : ${SCENARIO.padEnd(37)}║`);
    console.log(`║  Email    : ${data.email.padEnd(37)}║`);
    console.log('╚═══════════════════════════════════════════════════╝');
    console.log('');
    console.log('Key metrics to review:');
    console.log('  • http_req_duration   — Overall latency distribution');
    console.log('  • page_load_time      — Full page render times');
    console.log('  • api_response_time   — JSON/API endpoint speed');
    console.log('  • order_place_time    — Order creation latency');
    console.log('  • orders_placed       — Successful order count');
    console.log('  • orders_failed       — Failed order count');
    console.log('  • errors              — Logical error rate');
    console.log('  • http_req_failed     — Transport-level failure rate');
    console.log('');
}