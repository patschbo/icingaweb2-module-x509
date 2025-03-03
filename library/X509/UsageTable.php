<?php

// Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\X509;

use Icinga\Module\X509\Model\X509Certificate;
use Icinga\Web\Url;
use ipl\Html\Html;

/**
 * Table widget to display X.509 certificate usage
 */
class UsageTable extends DataTable
{
    protected $defaultAttributes = [
        'class'            => 'usage-table common-table table-row-selectable',
        'data-base-target' => '_next'
    ];

    public function createColumns()
    {
        return [
            'valid' => [
                'attributes' => ['class' => 'icon-col'],
                'column'     => function ($data) {
                    return $data->chain->valid;
                },
                'renderer'   => function ($valid) {
                    $icon = $valid ? 'check -ok' : 'block -critical';

                    return Html::tag('i', ['class' => "icon icon-{$icon}"]);
                }
            ],

            'hostname' => [
                'label'  => mt('x509', 'Hostname'),
                'column' => function ($data) {
                    return $data->chain->target->hostname;
                }
            ],

            'ip' => [
                'label'    => mt('x509', 'IP'),
                'column'   => function ($data) {
                    return $data->chain->target->ip;
                },
            ],

            'port' => [
                'label'  => mt('x509', 'Port'),
                'column' => function ($data) {
                    return $data->chain->target->port;
                }
            ],

            'subject' => mt('x509', 'Certificate'),

            'signature_algo' => [
                'label'    => mt('x509', 'Signature Algorithm'),
                'renderer' => function ($algo, $data) {
                    return "{$data->signature_hash_algo} with $algo";
                }
            ],

            'pubkey_algo' => [
                'label'    => mt('x509', 'Public Key'),
                'renderer' => function ($algo, $data) {
                    return "$algo {$data->pubkey_bits} bits";
                }
            ],

            'valid_to' => [
                'attributes' => ['class' => 'expiration-col'],
                'label'      => mt('x509', 'Expiration'),
                'renderer'   => function ($to, $data) {
                    return new ExpirationWidget($data->valid_from, $to);
                }
            ]
        ];
    }

    protected function renderRow(X509Certificate $row)
    {
        $tr = parent::renderRow($row);

        $url = Url::fromPath('x509/chain', ['id' => $row->chain->id]);

        $tr->getAttributes()->add(['href' => $url->getAbsoluteUrl()]);

        return $tr;
    }
}
