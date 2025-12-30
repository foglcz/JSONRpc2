<?php
/**
 * This file is part of The Lightbulb Project
 *
 * Copyright 2011 Pavel Ptacek and Animal Group
 *
 * @author Pavel Ptacek <birdie at animalgroup dot cz>
 * @copyright Pavel Ptacek and Animal Group <www dot animalgroup dot cz>
 * @license New BSD License
 */

////////////////////////////////////////////////////////////////////////////////
// Version 1.2.2
////////////////////////////////////////////////////////////////////////////////

if(!class_exists('InvalidStateException',false)) {
    class InvalidStateException extends Exception {}
}
