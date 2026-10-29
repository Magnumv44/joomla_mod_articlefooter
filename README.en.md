# mod_articlefooter

[English](README.md) | [Українська](README.uk.md)

A Joomla 6 site module that prints a custom HTML block at the end of articles from the categories you choose. Its main use case is a **"Go to the discussion" button** that leads to a separate forum topic created for each article, but it can output any HTML, CSS or JavaScript.

> **Note:** this is not a comment system. The module only outputs the HTML you give it. Comments or discussions live elsewhere (for example, on a phpBB forum).

## Features

- Shows only on the article page (`com_content`, `view=article`).
- Works only in the categories you select, optionally including their subcategories.
- Lets you exclude whole categories (with all subcategories) and individual articles by ID.
- Outputs arbitrary HTML, CSS and JavaScript without filtering.
- Takes a **different URL for every article** from a custom field and inserts it into the HTML through the `{forum_url}` placeholder.
- Hides itself when the custom field is empty, so no empty button appears before the forum topic exists.
- Optionally runs the HTML through content plugins (`{loadmodule}`, `{emailcloak}`, and so on).
- Built on the Joomla 6 module architecture: `AbstractModuleDispatcher`, a helper factory, a service provider and namespaces.
- Language files: English (en-GB) and Ukrainian (uk-UA).

## Requirements

- Joomla 6.x
- PHP 8.3 or newer

## Installation

1. Pack the module folder contents (the files `mod_articlefooter.xml`, `services/`, `src/`, `tmpl/` and `language/` must be at the archive root) into `mod_articlefooter.zip`.
2. In the Joomla administrator open **System → Install → Extensions** and upload the archive.
3. Updating is done the same way: install the new archive over the old one. The manifest uses `method="upgrade"`, so existing module settings are kept. Fields added in newer versions have to be filled in manually.

## Setup

### 1. Create a custom field for the topic URL

1. Go to **Content → Fields → New**.
2. Choose the type **URL** (or **Text**) and give it a name, for example "Forum topic".
3. Assign it to the categories where the module is used.
4. On the **Options** tab set **Automatic Display** to **Do not automatically display**. Otherwise Joomla prints the field value inside the article. The module does not depend on this setting, because it reads the value directly from the database.

### 2. Create the module

1. Go to **Content → Site Modules → New** and choose **Article Footer (custom HTML)**.
2. Configure it:

| Setting | Description |
| --- | --- |
| Categories | The module is shown only in articles from these categories. |
| Include subcategories | If enabled (the default), choosing a parent category also covers its child categories. |
| Excluded categories | Articles from these categories and all their subcategories never show the module, even if the category is selected above. |
| Excluded articles (IDs) | Comma-separated article IDs where the module is never shown, for example `12, 57, 103`. |
| Custom field with forum topic URL | The article custom field whose value replaces `{forum_url}`. |
| HTML code | The HTML that is output. By default it contains the discussion button. |
| Process content plugins | Runs the HTML through content plugins. |

3. Choose a **template position** that is rendered after the component output. In Cassiopeia that is `main-bottom`.
4. On the **Menu Assignment** tab choose **On all pages**. The module checks the category itself and shows only on suitable article pages. This also works for articles opened without a menu item.
5. Publish the module.

### 3. Daily workflow

1. Publish the article.
2. Create the topic on the forum.
3. Copy the topic address (for example `https://forum.example.com/viewtopic.php?t=123`).
4. Paste it into the custom field of the article and save.

From that moment the button appears under the article. While the field is empty, nothing is shown.

## Where the module is displayed

The module appears **only on the full article page** (`com_content`, `view=article`), under the article text.

It is **not** shown on the home page, in the featured articles view, in category lists or blogs, in search results or anywhere else where articles are listed as short intros, even if those articles belong to the selected categories. Since the button leads to the discussion of one specific article, it makes sense only on the full article page. The exception is a home page that is itself a single article: there the module is shown.

Showing a separate block under every item of a list would require a template override of that list and is not supported by this module.

## Module position in your template

The module needs a **template position that is rendered after the component output**. Cassiopeia already has one: `main-bottom`.

A custom template may not have such a position (for example, only `top-menu`, `left` and `footer`). In that case add a new one, for example `article-footer`.

1. In the template's `index.php`, right after the component:

```php
<jdoc:include type="message" />
<jdoc:include type="component" />
<?php if ($this->countModules('article-footer')) : ?>
    <jdoc:include type="modules" name="article-footer" style="none" />
<?php endif; ?>
```

2. In the template's `templateDetails.xml`, inside `<positions>`:

```xml
<position>article-footer</position>
```

3. Select this position in the module settings and set the menu assignment to **On all pages**.

Do not wrap the position in a styled `<div>` (with margins or padding) in the template. Joomla counts assigned modules, not their output, so such a wrapper would leave an empty gap under the articles where the module shows nothing. The module prints its own `<div class="mod-articlefooter">` only when there is something to show, so put spacing in CSS on that class:

```css
.mod-articlefooter {
    margin-top: 1.5rem;
}
```

## Default HTML

```html
<a class="btn btn-primary" href="{forum_url}" target="_blank" rel="noopener">Go to the discussion</a>
```

`btn btn-primary` are Bootstrap classes available in the Cassiopeia template. In another template replace them with your own classes. The output is wrapped in `<div class="mod-articlefooter">`, which you can use for styling.

## Placeholders

| Placeholder | Replaced with |
| --- | --- |
| `{forum_url}` | The value of the selected custom field. Only absolute `http://` and `https://` URLs are accepted, the value is HTML-escaped. If the value is empty or invalid, the module outputs nothing. |
| `{article_title}` | The article title (HTML-escaped). |
| `{article_id}` | The article ID. |

## How the module decides whether to show

1. It is the article page of `com_content`.
2. At least one category is selected in the module.
3. The article ID is not in the list of excluded articles.
4. The article category (or any of its parent categories) is not in the list of excluded categories.
5. The article category is among the selected ones (or, with subcategories enabled, one of its parents is).
6. If the HTML contains `{forum_url}`, the custom field contains a valid URL.

If any step fails, the module outputs nothing and Joomla does not render its wrapper.

## Updates and compatibility

The manifest declares an update server, so Joomla can notify you about new versions of the module (**System → Update → Extensions**) and show compatibility information before a Joomla core update. The update file is published on the author's site:

```
https://www.magnumblog.space/mod_articlefooter-update.xml
```

The `<targetplatform name="joomla" version="6.*"/>` line in that file declares that the module is built for Joomla 6.x.

### Releasing a new version (automatically)

Releases are built by GitHub Actions, see `.github/workflows/release.yml`.

**One-time setup**

1. Put `release.yml` into `.github/workflows/` in the repository and push it.
2. Make sure the workflow may create releases: in the repository open **Settings → Actions → General → Workflow permissions** and choose **Read and write permissions**. The workflow also requests `contents: write` itself, but an organization or repository policy can restrict it.
3. If the module is not in the repository root, set `MODULE_DIR` at the top of the workflow to its folder.

**Every release**

1. Raise `<version>` in `mod_articlefooter.xml` (for example to `1.2.2`) and commit.
2. Push the commit, then create and push a tag that starts with `v` and equals the version:

```
git tag v1.2.2
git push origin v1.2.2
```

3. Wait for the **Release** workflow on the **Actions** tab to finish. It checks that the tag equals the manifest version, validates PHP and XML, builds `mod_articlefooter.zip`, calculates the SHA-256 checksum, generates `mod_articlefooter-update.xml` and creates a GitHub release with both files attached.
4. Download `mod_articlefooter-update.xml` from the release assets and upload it to the site root, replacing the old one. After that Joomla sites see the new version.

If the workflow fails with a message that the tag does not match the manifest version, delete the tag, fix the version and tag again:

```
git tag -d v1.2.2
git push origin :refs/tags/v1.2.2
```

The update file generated by the workflow always matches the archive built in the same run, so do not mix it with files from other builds.

### Releasing manually

1. Raise `<version>` in `mod_articlefooter.xml`.
2. Build the archive `mod_articlefooter.zip` (the manifest must be at the archive root).
3. Publish the archive as a GitHub release with the tag `v<version>` (for example `v1.2.1`).
4. Calculate the checksum: `sha256sum mod_articlefooter.zip`.
5. In `mod_articlefooter-update.xml` update `<version>`, `<downloadurl>` and `<sha256>`, then upload the file to the site root.

The checksum must match the archive in the release exactly. If you rebuild the archive, recalculate it, otherwise Joomla will refuse the update. The update file is not part of the module archive and is only hosted on the site. If you move the repository, change the URLs in the update file and the update server URL in the manifest.

## Troubleshooting

- **Nothing is shown.** Check that the module is published, has a position that is rendered on the page, and is assigned to all pages. Make sure the article category is selected and not excluded.
- **The button is missing in one article.** Most likely the custom field is empty or does not start with `http://` or `https://`.
- **The URL is printed inside the article text.** Set the field option **Automatic Display** to **Do not automatically display**.
- **The module appears above the article.** Choose a position that is rendered after the component output.

## Security

- The HTML field is saved unfiltered, which is required to allow any HTML and JavaScript. Only trusted administrators should have permission to edit modules.
- The URL from the custom field is validated and escaped before it is inserted.

## Caching

The module deliberately has no cache settings. Joomla's module cache does not know which article is open and could show the same result in different articles.

## Project structure

```
mod_articlefooter/
├── mod_articlefooter.xml        manifest and parameters
├── services/provider.php        service provider (dispatcher and helper factories)
├── src/
│   ├── Dispatcher/Dispatcher.php   display logic and placeholders
│   └── Helper/ArticleFooterHelper.php   database queries
├── tmpl/default.php             output template
└── language/
    ├── en-GB/
    └── uk-UA/
```

Namespace: `Magnum\Module\ArticleFooter`.

## License

This module is licensed under the **GNU General Public License version 3 or later** (GPL-3.0-or-later). The full text is in the [LICENSE](LICENSE) file.

You may use, copy, modify and distribute the module, provided that you keep the copyright notices of the original author in the source files, mark your changes, and publish derivative works under the same license together with their source code.

## Author

Magnum — [magnumblog.space](https://www.magnumblog.space)
