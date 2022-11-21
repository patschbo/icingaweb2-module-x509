<?php

// Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\X509\Controllers;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\X509\Controller;
use Icinga\Module\X509\Model\X509Certificate;
use Icinga\Module\X509\UsageTable;
use Icinga\Module\X509\Web\Control\SearchBar\ObjectSuggestions;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class UsageController extends Controller
{
    public function indexAction()
    {
        $this->getTabs()->enableDataExports();
        $this->addTitleTab($this->translate('Certificate Usage'));

        try {
            $conn = $this->getDb();
        } catch (ConfigurationError $_) {
            $this->render('missing-resource', null, true);
            return;
        }

        $targets = X509Certificate::on($conn)
            ->with(['chain', 'chain.target'])
            ->withColumns([
                'chain.id',
                'chain.valid',
                'chain.target.ip',
                'chain.target.port',
                'chain.target.hostname',
            ]);

        $targets
            ->getSelectBase()
            ->where(new Expression('certificate_link.order = 0'));

        $sortColumns = [
            'chain.target.hostname' => $this->translate('Hostname'),
            'chain.target.ip'       => $this->translate('IP'),
            'chain.target.port'     => $this->translate('Port'),
            'subject'               => $this->translate('Certificate'),
            'issuer'                => $this->translate('Issuer'),
            'version'               => $this->translate('Version'),
            'self_signed'           => $this->translate('Is Self-Signed'),
            'ca'                    => $this->translate('Is Certificate Authority'),
            'trusted'               => $this->translate('Is Trusted'),
            'pubkey_algo'           => $this->translate('Public Key Algorithm'),
            'pubkey_bits'           => $this->translate('Public Key Strength'),
            'signature_algo'        => $this->translate('Signature Algorithm'),
            'signature_hash_algo'   => $this->translate('Signature Hash Algorithm'),
            'valid_from'            => $this->translate('Valid From'),
            'valid_to'              => $this->translate('Valid To'),
            'chain.valid'           => $this->translate('Chain Is Valid'),
            'duration'              => $this->translate('Duration'),
            'expires'               => $this->translate('Expiration')
        ];

        $limitControl = $this->createLimitControl();
        $paginator = $this->createPaginationControl($targets);
        $sortControl = $this->createSortControl($targets, $sortColumns);

        $searchBar = $this->createSearchBar($targets, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $targets->peekAhead($this->view->compact);

        $targets->filter($filter);

        $this->addControl($paginator);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $exportable = array_flip([
            'valid', 'hostname', 'ip', 'port', 'subject', 'issuer', 'version',
            'self_signed', 'ca', 'trusted', 'pubkey_algo', 'pubkey_bits',
            'signature_algo', 'signature_hash_algo', 'valid_from', 'valid_to'
        ]);

        $this->handleFormatRequest($targets, function (Query $targets) use ($conn, $exportable) {
            foreach ($targets as $usage) {
                $usage['valid_from'] = (new \DateTime())
                    ->setTimestamp($usage['valid_from'])
                    ->format('l F jS, Y H:i:s e');
                $usage['valid_to'] = (new \DateTime())
                    ->setTimestamp($usage['valid_to'])
                    ->format('l F jS, Y H:i:s e');

                $usage->ip = $usage->chain->target->ip;
                $usage->hostname = $usage->chain->target->hostname;
                $usage->port = $usage->chain->target->port;
                $usage->valid = $usage->chain->valid;

                yield array_intersect_key(iterator_to_array($usage), $exportable);
            }
        });

        $this->addContent((new UsageTable())->setData($targets));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate(); // Updates the browser search bar
        }
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(X509Certificate::class);
        $suggestions->forRequest($this->getServerRequest());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(X509Certificate::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
