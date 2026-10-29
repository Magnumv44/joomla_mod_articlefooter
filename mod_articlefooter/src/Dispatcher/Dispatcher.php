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

namespace Magnum\Module\ArticleFooter\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        // Порожній рядок => шаблон нічого не виводить => обгортка модуля не рендериться
        $data['html'] = $this->buildHtml($data['params']);

        return $data;
    }

    /**
     * Готує HTML: перевіряє умови показу, підставляє плейсхолдери.
     * Якщо в коді є {forum_url}, а в матеріалі поле порожнє або містить
     * некоректне посилання — модуль нічого не виводить.
     */
    private function buildHtml(Registry $params): string
    {
        $html = (string) $params->get('html', '');

        if (trim($html) === '' || !$this->shouldDisplay($params)) {
            return '';
        }

        $articleId = $this->input->getInt('id');
        $helper    = $this->getHelperFactory()->getHelper('ArticleFooterHelper');
        $replace   = ['{article_id}' => (string) $articleId];

        if (str_contains($html, '{forum_url}')) {
            $url = $helper->getFieldValue($articleId, (int) $params->get('forum_field', 0));

            // Лише абсолютні http(s)-посилання (відсікає javascript: тощо)
            if (!preg_match('#^https?://\S+$#i', $url)) {
                return '';
            }

            $replace['{forum_url}'] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }

        if (str_contains($html, '{article_title}')) {
            $replace['{article_title}'] = htmlspecialchars($helper->getTitle($articleId), ENT_QUOTES, 'UTF-8');
        }

        $html = strtr($html, $replace);

        if ((int) $params->get('prepare_content', 0) === 1) {
            $html = HTMLHelper::_('content.prepare', $html, '', 'mod_articlefooter.content');
        }

        return $html;
    }

    /**
     * Модуль показується лише на сторінці матеріалу com_content з вибраної категорії,
     * якщо сам матеріал або його категорія не додані у винятки.
     */
    private function shouldDisplay(Registry $params): bool
    {
        $input = $this->input;

        if ($input->getCmd('option') !== 'com_content' || $input->getCmd('view') !== 'article') {
            return false;
        }

        $articleId = $input->getInt('id');

        if (!$articleId) {
            return false;
        }

        $selected = $this->toIntList($params->get('categories', []));

        if (!$selected) {
            return false;
        }

        // Виняток за ID матеріалу (без запиту до БД)
        $excludedArticles = $this->toIntList(
            preg_split('/[\s,;]+/', (string) $params->get('excluded_articles', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        if (\in_array($articleId, $excludedArticles, true)) {
            return false;
        }

        $info = $this->getHelperFactory()
            ->getHelper('ArticleFooterHelper')
            ->getCategoryInfo($articleId);

        if (!$info['path']) {
            return false;
        }

        // Виняток за категорією — завжди разом із підкатегоріями
        $excludedCategories = $this->toIntList($params->get('excluded_categories', []));

        if (array_intersect($info['path'], $excludedCategories)) {
            return false;
        }

        // Вибір категорій: з урахуванням підкатегорій (предки) або лише власна категорія
        $candidates = (bool) $params->get('include_subcategories', 1) ? $info['path'] : [$info['catid']];

        return (bool) array_intersect($candidates, $selected);
    }

    /**
     * @param   mixed  $value
     *
     * @return  int[]
     */
    private function toIntList($value): array
    {
        return array_values(array_filter(array_map('intval', (array) $value)));
    }
}
