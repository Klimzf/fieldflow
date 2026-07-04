import { expect, test } from '@playwright/test'

test('opens the FieldFlow application', async ({ page }) => {
  const response = await page.goto('/')

  expect(response).not.toBeNull()
  expect(response?.ok()).toBeTruthy()

  await expect(page).toHaveTitle('FieldFlow')
  await expect(page.locator('#app')).toBeVisible()
  await expect(page.locator('#app')).not.toBeEmpty()
})
