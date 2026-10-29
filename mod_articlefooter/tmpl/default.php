<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articlefooter
 *
 * @copyright   (C) 2026 Magnum <https://www.magnumblog.space>
 * @license     GNU General Public License version 3 or later; see LICENSE
 *
 * Developed by Magnum https://www.magnumblog.space
 * When copying or modifying, keep this copyright notice.
 */

\defined('_JEXEC') or die;

/** @var string $html */

if (trim($html) === '') {
    return;
}

echo '<div class="mod-articlefooter">' . $html . '</div>';
