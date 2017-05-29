module=     agentinstaller

srcs= \
controllers/ConfigController.php \
controllers/DownloadController.php \
controllers/IndexController.php \
forms/Config/GeneralConfigForm.php \
forms/CreateInstallerForm.php \
configuration.php

APPLICATION=   ${module}/application
MODULEPATH= /usr/share/icingaweb2/modules

# Build the entire module, including controllers, forms, views and configuration
# files. Once done, set sane permissions for the PHP source files.
build: ${srcs} ${APPLICATION} ${APPLICATION}/controllers \
    ${APPLICATION}/forms ${APPLICATION}/views \
    ${module}/configuration.php ${module}/module.info
	chmod -R 444 ${APPLICATION}

${APPLICATION}:
	mkdir -p $@

# The rules for controllers, forms and views targets just consist of syntax checks
# for now.
${APPLICATION}/controllers: ${srcs}
	@echo "Building $@..."
	@for f in ${srcs}; do \
		echo "Checking $$f for syntax errors..."; \
		php -l $$f; \
	done
	@cp -R controllers $@

${APPLICATION}/forms: ${srcs}
	@echo "Building $@..."
	@for f in ${srcs}; do \
		echo "Checking $$f for syntax errors..."; \
		php -l $$f; \
	done
	@cp -R forms $@

${APPLICATION}/views: views
	@echo "Building $@..."
	@cp -R views $@

${module}/configuration.php: configuration.php
	@echo "Building $@..."
	@php -l configuration.php
	@cp configuration.php $@

${module}/module.info: module.info
	@cp module.info $@

install: build
	cp -R ${module} ${MODULEPATH}

.PHONY: clean
clean:
	rm -rf ${module}
