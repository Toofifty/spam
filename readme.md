Simple PHP Apache MySQL environment for macOS
=============================================

Inspired by Laravel Valet, but specifically created for sites that still rely on Apache and the LAMP / MAMP stack.

# Install

Grab it with composer-

```bash
composer global require toofifty/spam
spam install
```

# Add a site

Add a site to SPAM with the following command. If the sitename is absent, the current directory name is used as the domain.

```bash
# cd projects/my-website
spam link
# => creates http://my-website.test/
spam link our-website
# => creates http://our-website.test/
```

Many domains can point to the same directory. The unlink command works the same way-

```bash
# cd projects/my-website
spam unlink
# => disables http://my-website.test/
spam unlink our-website
# => disables http://our-website.test/
```

# Changing PHP versions

You can change the version of PHP at any time - this will apply to all sites, and will change the PHP in your `$PATH`.

```bash
php -v
# => php 7.1.27
spam use php@5.6
php -v
# => php 5.6.40
spam use php@latest
php -v
# => php 7.3.2
```