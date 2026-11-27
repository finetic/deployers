variable "PHP_VERSION" {
  default = "8.5"
}

variable "REPOSITORY" {
  default = "docker.finetic.dev/finetic/runtime/frankenphp"
}

variable "FRANKENPHP_IMAGES" {
  type = map(string)
  default = {
    "8.4" = "dunglas/frankenphp:1-php8.4-trixie"
    "8.5" = "dunglas/frankenphp:1-php8.5-trixie"
  }
}

group "default" {
  targets = ["smoke"]
}

target "runtime" {
  context = "."
  dockerfile = "Dockerfile"
  args = {
    FRANKENPHP_IMAGE = FRANKENPHP_IMAGES[PHP_VERSION]
  }
}

target "smoke" {
  inherits = ["runtime"]
  matrix = {
    arch = ["amd64", "arm64"]
  }
  name = "smoke-${arch}"
  platforms = ["linux/${arch}"]
  tags = ["frankenphp-runtime-test:${arch}"]
  output = ["type=docker"]
}

target "publish" {
  inherits = ["runtime"]
  platforms = ["linux/amd64", "linux/arm64"]
  tags = ["${REPOSITORY}:frankenphp-php${PHP_VERSION}"]
  output = ["type=registry"]
}
