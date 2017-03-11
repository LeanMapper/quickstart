tester = vendor/bin/tester
tests_dir = tests/
php_ini = $(tests_dir)php-unix.ini
php_bin = php

.PHONY: test coverage clean
test:
		@$(tester) -p $(php_bin) -c $(php_ini) $(tests_dir)
