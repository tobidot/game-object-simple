<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
NAME ?= <?= $config->name ?>
KIND ?= <?= $config->kind ?>
VERSION := $(shell cat VERSION 2>/dev/null || echo "<?= $config->version ?>")

.PHONY: all build tce clean

all: build

build:
	npm run build

tce: build
	rm -rf build-tce
	mkdir -p build-tce/dist releases
	cp -r dist/* build-tce/dist/
	cd build-tce && zip -r ../releases/$(NAME)-v$(VERSION).zip dist
	@echo "Created package: releases/$(NAME)-v$(VERSION).zip"

clean:
	rm -rf dist build-tce
