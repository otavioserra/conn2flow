import { test, expect } from '@playwright/test';

const publicPath = process.env.C2F_E2E_PUBLISHER_INDEX_PATH;

test.describe('fluxo do publicador indice', () => {
  test.skip(!publicPath, 'Defina C2F_E2E_PUBLISHER_INDEX_PATH para rodar este fluxo.');

  test('busca, ordena e carrega mais itens por AJAX', async ({ page }) => {
    await page.goto(publicPath);

    const root = page.locator('.conn2flow-publisher-index').first();
    await expect(root).toBeVisible();

    await root.locator('.publisher-index-search').fill('teste');
    await expect(root.locator('.publisher-index-items')).toBeVisible();

    const sort = root.locator('.publisher-index-sort');
    if (await sort.count()) {
      await sort.selectOption({ index: 0 });
    }

    const loadMore = root.locator('.publisher-index-load-more:not(.hidden)').first();
    if (await loadMore.count()) {
      const before = await root.locator('.publisher-index-items > *').count();
      await loadMore.click();
      await expect.poll(async () => root.locator('.publisher-index-items > *').count()).toBeGreaterThan(before);
    }
  });
});
