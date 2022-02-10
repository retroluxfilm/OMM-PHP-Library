<?php
/*
 * OMM PHP Library
 * Copyright (c) 2022. Alexandre Miguel Maia
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

namespace OMM\Task;

use Exception;

abstract class Task
{

    /**
     * command line name for this task
     */
    public static string $commandName;

    /**
     * Shows the help of the task for the command line
     */
    abstract public static function showHelp(): void;

    /**
     * Runs the task with the given arguments array from the command line
     * @param array $arguments command line arguments
     * @throws Exception
     */
    abstract public static function runTask(array $arguments) : void;
}