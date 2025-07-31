package error_handler

import (
	"github.com/limanmys/core/internal/validation"
	"github.com/limanmys/core/utils"

	"github.com/gofiber/fiber/v3"
)

var ErrorHandler = func(c fiber.Ctx, err error) error {
	code := fiber.StatusInternalServerError
	if e, ok := err.(*validation.Errors); ok {
		code = fiber.StatusUnprocessableEntity
		return c.Status(code).JSON(e)
	}
	if _, ok := err.(*utils.AuthError); ok {
		code = fiber.StatusUnauthorized
	}
	if _, ok := err.(*utils.AccessError); ok {
		code = fiber.StatusForbidden
	}
	if e, ok := err.(*fiber.Error); ok {
		code = e.Code
	}
	var message interface{}
	if code == fiber.StatusOK {
		message = struct {
			Message string `json:"message"`
		}{err.Error()}
	} else {
		message = struct {
			Error string `json:"error"`
		}{err.Error()}
	}
	return c.Status(code).JSON(message)
}
