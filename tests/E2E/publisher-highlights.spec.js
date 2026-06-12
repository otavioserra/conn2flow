import { test, expect } from '@playwright/test';

const runPublisherFlows = process.env.C2F_E2E_PUBLISHER_FLOWS === '1';

test.describe('fluxo de publicacao e renderizacao de destaques', () => {
  test.skip(!runPublisherFlows, 'Defina C2F_E2E_PUBLISHER_FLOWS=1 e prepare dados de teste para rodar este fluxo.');

  test('renderiza um componente de destaques no site publico', async ({ page }) => {
    await page.goto(process.env.C2F_E2E_HIGHLIGHTS_PUBLIC_PATH || '/');
    const widget = page.locator('.conn2flow-publisher-highlights, [data-widget="publisher-highlights"]').first();

    await expect(widget).toBeVisible();
    await expect(widget).toHaveCSS('display', /block|grid|flex/);
  });
});
