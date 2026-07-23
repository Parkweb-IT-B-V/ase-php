<?php

namespace ParkWeb\Ase;

enum Level: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Fatal = 'fatal';
}
