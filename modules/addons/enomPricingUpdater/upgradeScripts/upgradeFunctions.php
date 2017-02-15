<?php

/**
 *
 *                  WHMCS eNom price sync addon module
 *                   Copyright (C) 2017  Duco Hosting
 *
 *        This program is free software: you can redistribute it and/or modify
 *        it under the terms of the GNU General Public License as published by
 *        the Free Software Foundation, either version 3 of the License, or
 *        (at your option) any later version.
 *
 *        This program is distributed in the hope that it will be useful,
 *        but WITHOUT ANY WARRANTY; without even the implied warranty of
 *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *        GNU General Public License for more details.
 *
 *        You should have received a copy of the GNU General Public License
 *        along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use Illuminate\Database\Capsule\Manager as Capsule;

function compareTableRowCount($oldTable, $newTable) {
    $oldCount = Capsule::table($oldTable)->count();
    $newCount = Capsule::table($newTable)->count();

    if($oldCount !== $newCount) {
        throw new Exception("Table comparison failed: $oldTable and $newTable do not contain the same amount of rows. Old: $oldCount, New: $newCount");
    }

    return true;
}
