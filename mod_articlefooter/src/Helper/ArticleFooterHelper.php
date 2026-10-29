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

namespace Magnum\Module\ArticleFooter\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;

class ArticleFooterHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Повертає власну категорію матеріалу та ланцюжок її предків.
     *
     * @return  array{catid: int, path: int[]}  catid — категорія матеріалу,
     *                                          path — вона ж + усі батьківські
     */
    public function getCategoryInfo(int $articleId): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('c.id', 'id'), $db->quoteName('ac.id', 'catid')])
            ->from($db->quoteName('#__content', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'ac'), $db->quoteName('ac.id') . ' = ' . $db->quoteName('a.catid'))
            ->join(
                'INNER',
                $db->quoteName('#__categories', 'c'),
                $db->quoteName('c.lft') . ' <= ' . $db->quoteName('ac.lft')
                . ' AND ' . $db->quoteName('c.rgt') . ' >= ' . $db->quoteName('ac.rgt')
                . ' AND ' . $db->quoteName('c.extension') . ' = ' . $db->quote('com_content')
            )
            ->where($db->quoteName('a.id') . ' = :articleId')
            ->bind(':articleId', $articleId, ParameterType::INTEGER);

        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        return [
            'catid' => $rows ? (int) $rows[0]->catid : 0,
            'path'  => array_map(static fn ($row) => (int) $row->id, $rows),
        ];
    }

    /**
     * Значення користувацького поля матеріалу (порожній рядок, якщо не заповнено).
     */
    public function getFieldValue(int $articleId, int $fieldId): string
    {
        if ($fieldId <= 0) {
            return '';
        }

        $itemId = (string) $articleId;
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true)
            ->select($db->quoteName('value'))
            ->from($db->quoteName('#__fields_values'))
            ->where($db->quoteName('field_id') . ' = :fieldId')
            ->where($db->quoteName('item_id') . ' = :itemId')
            ->bind(':fieldId', $fieldId, ParameterType::INTEGER)
            ->bind(':itemId', $itemId, ParameterType::STRING)
            ->setLimit(1);

        return trim((string) $db->setQuery($query)->loadResult());
    }

    /**
     * Заголовок матеріалу.
     */
    public function getTitle(int $articleId): string
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = :articleId')
            ->bind(':articleId', $articleId, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult();
    }
}
