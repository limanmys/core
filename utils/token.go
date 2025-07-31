package utils

import (
	"time"

	"github.com/gofiber/fiber/v3"
	"github.com/golang-jwt/jwt/v5"

	"github.com/limanmys/core/internal/config"
)

type Claim struct {
	Username string
	ID       uint
}

func CreateToken(username string, id uint) (string, error) {
	token := jwt.New(jwt.SigningMethodHS256)

	claims := token.Claims.(jwt.MapClaims)
	claims["username"] = username
	claims["user_id"] = id
	claims["exp"] = time.Now().Add(time.Hour * 72).Unix()

	conf, err := config.Load()
	if err != nil {
		return "", err
	}

	appKey := conf.App.Key
	if appKey == "" {
		appKey = "liman-key" // Default key if not set
	}

	return token.SignedString([]byte(appKey))
}

func GetClaimFromContext(c fiber.Ctx) Claim {
	user := c.Locals("token").(*jwt.Token)
	claims := user.Claims.(jwt.MapClaims)
	return Claim{
		Username: claims["username"].(string),
		ID:       uint(claims["user_id"].(float64)),
	}
}
