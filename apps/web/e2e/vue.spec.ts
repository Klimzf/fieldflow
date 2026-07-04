import { expect, test } from '@playwright/test'

test('opens the FieldFlow application', async ({ page }) => {
  await page.goto('/')

  await expect(page).toHaveTitle(/FieldFlow/)
  await expect(page.locator('#app')).toBeVisible()
  await expect(page.locator('#app')).not.toBeEmpty()
})
