package middleware

import "github.com/gofiber/fiber/v3"

func TokenLookup(c fiber.Ctx) error {
	// If cookies has token, convert it to Authorization header
	if cookie := c.Cookies("token"); cookie != "" {
		c.Request().Header.Set("Authorization", "Bearer "+cookie)
	}

	return c.Next()
}
