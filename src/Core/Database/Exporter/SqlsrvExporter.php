<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

/**
 * The Exporter class.
 *
 * @since  3.5
 */
class SqlsrvExporter extends AbstractExporter
{
    /**
     * export
     *
     * @param  string  $file
     *
     * @return mixed|string
     */
    public function export(string $file)
    {
        echo 'Sqlsrv exporter not yet prepared.';

        return '';
    }

    /**
     * getCreateTable
     *
     * @param $table
     *
     * @return array|mixed|string
     */
    protected function getCreateTable($table)
    {
    }

    /**
     * getInserts
     *
     * @param $table
     *
     * @return mixed|null|string
     */
    protected function getInserts($table)
    {
    }
}
