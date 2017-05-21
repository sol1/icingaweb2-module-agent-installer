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
    ${APPLICATION}/configuration.php ${APPLICATION}/module.info
	@for f in ${srcs}; do \
		chmod 444 ${APPLICATION}/$$f; \
	done

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
	cp -R controllers $@

${APPLICATION}/forms: ${srcs}
	@echo "Building $@..."
	@for f in ${srcs}; do \
		echo "Checking $$f for syntax errors..."; \
		php -l $$f; \
	done
	cp -R forms $@

${APPLICATION}/views: views
	@echo "building $@..."
	cp -R views $@

${APPLICATION}/configuration.php: configuration.php
	php -l configuration.php
	cp configuration.php $@

${APPLICATION}/module.info: module.info
	cp module.info $@

install: build
	cp -R ${module} ${MODULEPATH}

.PHONY: clean
clean:
	rm -rf ${module}
