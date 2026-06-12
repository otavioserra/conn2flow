import { test, expect } from '@playwright/test';

const hasCredentials = Boolean(process.env.C2F_E2E_USER && process.env.C2F_E2E_PASSWORD);

test.describe('fluxo de login e perfil', () => {
  test.skip(!hasCredentials, 'Defina C2F_E2E_USER e C2F_E2E_PASSWORD para rodar este fluxo.');

  test('login no painel e acesso ao perfil do usuario', async ({ page }) => {
    await page.goto('/gestor/');
    await page.getByLabel(/email|usuario|login/i).fill(process.env.C2F_E2E_USER);
    await page.getByLabel(/senha/i).fill(process.env.C2F_E2E_PASSWORD);
    await page.getByRole('button', { name: /acessar|entrar|login/i }).click();

    await expect(page).toHaveURL(/dashboard|gestor/i);
    await page.getByRole('link', { name: /perfil|usuario/i }).click();
    await expect(page.getByRole('heading', { name: /perfil|usuario/i })).toBeVisible();
  });
});
