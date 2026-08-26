#!/usr/bin/env node
/**
 * Conn2Flow — Headless Page Inspector
 * Invoked by: c2f page:inspect
 *
 * Receives base64-encoded JSON config as first argument.
 * Returns structured JSON to stdout.
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    // Parse config from base64 argument
    const configB64 = process.argv[2];
    if (!configB64) {
        console.error('Usage: node page-inspect.js <base64_config>');
        process.exit(1);
    }

    let config;
    try {
        config = JSON.parse(Buffer.from(configB64, 'base64').toString('utf-8'));
    } catch (e) {
        console.error('Failed to parse config:', e.message);
        process.exit(1);
    }

    const {
        url,
        selectors = [],
        computedProperties = [],
        screenshot = false,
        screenshotPath = null,
        cookiesPath = null,
    } = config;

    const result = {
        status: null,
        url: url,
        consoleErrors: [],
        elements: [],
        screenshotPath: null,
    };

    let browser;
    try {
        browser = await chromium.launch({ headless: true });
        const context = await browser.newContext();

        // Load cookies from Netscape cookie jar if provided
        if (cookiesPath && fs.existsSync(cookiesPath)) {
            const cookieJar = fs.readFileSync(cookiesPath, 'utf-8');
            const cookies = [];
            for (const line of cookieJar.split('\n')) {
                const trimmed = line.trim();
                if (!trimmed || trimmed.startsWith('#')) continue;
                const parts = trimmed.split('\t');
                if (parts.length >= 7) {
                    const [domain, , urlPath, secure, expires, name, value] = parts;
                    cookies.push({
                        name,
                        value,
                        domain: domain.startsWith('.') ? domain : domain,
                        path: urlPath || '/',
                        secure: secure.toUpperCase() === 'TRUE',
                        httpOnly: true,
                        expires: parseInt(expires, 10) || -1,
                    });
                }
            }
            if (cookies.length > 0) {
                await context.addCookies(cookies);
            }
        }

        const page = await context.newPage();

        // Capture console errors
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                result.consoleErrors.push(msg.text());
            }
        });

        page.on('pageerror', (err) => {
            result.consoleErrors.push(err.message);
        });

        // Navigate
        const response = await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000,
        });

        result.status = response ? response.status() : null;

        // Inspect elements
        for (const selector of selectors) {
            const elementResult = {
                selector,
                found: false,
                computedStyles: {},
                activeAnimationsCount: 0,
            };

            try {
                const handle = await page.$(selector);
                if (handle) {
                    elementResult.found = true;

                    // Extract computed styles
                    if (computedProperties.length > 0) {
                        elementResult.computedStyles = await handle.evaluate(
                            (el, props) => {
                                const cs = window.getComputedStyle(el);
                                const result = {};
                                for (const prop of props) {
                                    result[prop] = cs.getPropertyValue(prop);
                                }
                                return result;
                            },
                            computedProperties
                        );
                    }

                    // Count active animations
                    elementResult.activeAnimationsCount = await handle.evaluate(
                        (el) => (el.getAnimations ? el.getAnimations().length : 0)
                    );
                }
            } catch (e) {
                elementResult.error = e.message;
            }

            result.elements.push(elementResult);
        }

        // Screenshot
        if (screenshot && screenshotPath) {
            const dir = path.dirname(screenshotPath);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
            await page.screenshot({ path: screenshotPath, fullPage: true });
            result.screenshotPath = screenshotPath;
        }

        await browser.close();
    } catch (e) {
        if (browser) await browser.close();
        console.error('Inspect error:', e.message);
        process.exit(1);
    }

    // Output JSON result
    console.log(JSON.stringify(result, null, 2));
})();
