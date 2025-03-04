<?php

namespace Icinga\Module\X509\Model;

use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class X509CertificateChainLink extends Model
{
    public function getTableName()
    {
        return 'x509_certificate_chain_link';
    }

    public function getTableAlias(): string
    {
        return 'link';
    }

    public function getKeyName()
    {
        return ['certificate_chain_id', 'certificate_id', 'order'];
    }

    public function getColumns()
    {
        return ['ctime'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp(['ctime']));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('certificate', X509Certificate::class)
            ->setCandidateKey('certificate_id');
        $relations->belongsTo('chain', X509CertificateChain::class)
            ->setCandidateKey('certificate_chain_id');
    }
}
