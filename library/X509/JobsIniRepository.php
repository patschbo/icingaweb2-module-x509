<?php

// Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\X509;

use Icinga\Repository\IniRepository;

/**
 * Collection of jobs stored in the jobs.ini file
 */
class JobsIniRepository extends IniRepository
{
    protected $queryColumns = ['jobs' => ['name', 'cidrs', 'ports', 'exclude_targets', 'schedule', 'frequencyType']];

    protected $configs = array('jobs' => array(
        'module'    => 'x509',
        'name'      => 'jobs',
        'keyColumn' => 'name'
    ));
}
