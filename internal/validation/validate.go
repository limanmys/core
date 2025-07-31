package validation

import (
	"reflect"

	"github.com/go-playground/locales/tr"
	ut "github.com/go-playground/universal-translator"
	"github.com/go-playground/validator/v10"
	tr_translations "github.com/go-playground/validator/v10/translations/tr"
)

var (
	translate ut.Translator
	validate  *validator.Validate
)

func init() {
	tr := tr.New()
	uni := ut.New(tr, tr)
	translate, _ = uni.GetTranslator("tr")
	validate = validator.New()
	err := tr_translations.RegisterDefaultTranslations(validate, translate)
	if err != nil {
		panic(err)
	}
}

type Error struct {
	Field string `json:"field"`
	Tag   string `json:"tag"`
	Value string `json:"value"`
	Error string `json:"error"`
}

type Errors struct {
	Message string             `json:"message"`
	Errors  map[string][]Error `json:"errors"`
}

func (e *Errors) Error() string {
	return e.Message
}

func Validate(s interface{}) error {
	err := validate.Struct(s)
	errors := &Errors{
		Message: "Check the fields.",
		Errors:  make(map[string][]Error),
	}
	if err != nil {
		for _, err := range err.(validator.ValidationErrors) {
			errors.Errors[FindJsonTagName(s, err.Field())] = append(errors.Errors[FindJsonTagName(s, err.Field())],
				Error{
					Field: FindJsonTagName(s, err.Field()),
					Error: err.Translate(translate),
					Tag:   err.Tag(),
					Value: err.Param(),
				})
		}
		return errors
	}
	return nil
}

func FindJsonTagName(i interface{}, original string) string {
	reflected := reflect.ValueOf(i)

	switch reflected.Kind() {
	case reflect.Ptr:
		reflected = reflected.Elem()
	case reflect.Struct:
		break
	default:
		return original
	}

	for i := 0; i < reflected.Type().NumField(); i++ {
		f := reflected.Type().Field(i)
		if f.Name == original {
			tag := f.Tag.Get("json")
			if tag != "" {
				return tag
			} else {
				return original
			}
		}
	}
	return original
}
