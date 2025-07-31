package middleware

import "github.com/gofiber/fiber/v3"

func IsAdmin(c fiber.Ctx) error {
	return c.Next()
}
