<?php

/**
 * Admin page for adding and editing addresses stored on accounts.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountAddressEdit extends AdminDBEdit
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var StoreCountry
     */
    protected $country;

    /**
     * @var StoreAccount
     */
    protected $account;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        $this->initAccount();
        $this->initDefaultAddressFields();

        $this->fields = [
            'text:fullname',
            'text:line1',
            'text:line2',
            'text:city',
            'integer:provstate',
            'text:provstate_other',
            'text:country',
            'text:postal_code',
            'text:phone',
            'text:company',
        ];
    }

    protected function initAccount()
    {
        if ($this->id === null) {
            $account_id = $this->app->initVar('account');
        } else {
            $account_id = SwatDB::queryOne(
                $this->app->db,
                sprintf(
                    'select account from AccountAddress where id = %s',
                    $this->app->db->quote($this->id, 'integer')
                )
            );
        }

        $class_name = SwatDBClassMap::get(StoreAccount::class);
        $this->account = new $class_name();
        $this->account->setDatabase($this->app->db);

        if (!$this->account->load($account_id)) {
            throw new AdminNotFoundException(sprintf(
                'Address cannot be ' .
                "edited because an account with id '%s' does not exist.",
                $account_id
            ));
        }

        if ($this->id === null) {
            $fullname_widget = $this->ui->getWidget('fullname');
            $fullname_widget->value = $this->account->getFullname();
        }
    }

    protected function initDefaultAddressFields()
    {
        // if this address is already the default billing address, desensitize
        // the checkbox
        $account = $this->account;
        $billing_id = $account->getInternalValue('default_billing_address');
        if ($billing_id !== null && $this->id !== null
            && $this->id === $billing_id) {
            $billing_field =
                $this->ui->getWidget('default_billing_address')->parent;

            $billing_field->sensitive = false;
            $billing_field->note = Store::_(
                'This address is already the default billing address.'
            );
        }

        // if this address is already the default shipping address, desensitize
        // the checkbox
        $shipping_id = $account->getInternalValue('default_shipping_address');
        if ($shipping_id !== null && $this->id !== null
            && $this->id === $shipping_id) {
            $shipping_field =
                $this->ui->getWidget('default_shipping_address')->parent;

            $shipping_field->sensitive = false;
            $shipping_field->note = Store::_(
                'This address is already the default shipping address.'
            );
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/addressedit.xml';
    }

    // process phase

    public function process()
    {
        if ($this->ui->getWidget('edit_form')->isSubmitted()) {
            // set provsate and country on postal code entry
            $country = $this->getCountry();
            $postal_code = $this->ui->getWidget('postal_code');
            $provstate = $this->ui->getWidget('provstate');

            if ($country->id === null) {
                return;
            }

            $provstate->process();

            if ($provstate->value === 'other') {
                $this->ui->getWidget('provstate_other')->required = true;
            } elseif ($provstate->value !== null) {
                $sql = sprintf(
                    'select abbreviation from ProvState
					where id = %s',
                    $this->app->db->quote($provstate->value)
                );

                $provstate_abbreviation =
                    SwatDB::queryOne($this->app->db, $sql);

                $postal_code->country = $country->id;
                $postal_code->provstate = $provstate_abbreviation;
            }

            if (!$country->has_postal_code) {
                $postal_code->required = false;
            }
        }

        parent::process();
    }

    protected function getCountry()
    {
        if (!$this->country instanceof StoreCountry) {
            $country_widget = $this->ui->getWidget('country');
            $country_widget->process();
            $country_id = $country_widget->value;

            $class_name = SwatDBClassMap::get(StoreCountry::class);
            $this->country = new $class_name();
            $this->country->setDatabase($this->app->db);
            $this->country->load($country_id);
        }

        return $this->country;
    }

    protected function saveDBData(): void
    {
        $values = $this->getUIValues();

        if ($this->id === null) {
            $this->fields[] = 'date:createdate';
            $date = new SwatDate();
            $date->toUTC();
            $values['createdate'] = $date->getDate();

            $this->fields[] = 'integer:account';
            $values['account'] = $this->account->id;

            $this->id = SwatDB::insertRow(
                $this->app->db,
                'AccountAddress',
                $this->fields,
                $values,
                'integer:id'
            );
        } else {
            SwatDB::updateRow(
                $this->app->db,
                'AccountAddress',
                $this->fields,
                $values,
                'id',
                $this->id
            );
        }

        // save default billing address
        if ($this->ui->getWidget('default_billing_address')->value) {
            $sql = sprintf(
                'update Account set default_billing_address = %s
				where id = %s',
                $this->app->db->quote($this->id, 'integer'),
                $this->app->db->quote($this->account->id, 'integer')
            );

            SwatDB::exec($this->app->db, $sql);
        }

        // save default shipping address
        if ($this->ui->getWidget('default_shipping_address')->value) {
            $sql = sprintf(
                'update Account set default_shipping_address = %s
				where id = %s',
                $this->app->db->quote($this->id, 'integer'),
                $this->app->db->quote($this->account->id, 'integer')
            );

            SwatDB::exec($this->app->db, $sql);
        }

        $message = new SwatMessage(sprintf(
            Store::_('Address for “%s” has been saved.'),
            $this->account->getFullName()
        ));

        $this->app->messages->add($message);
    }

    protected function getUIValues()
    {
        $values = $this->ui->getValues([
            'fullname',
            'line1',
            'line2',
            'city',
            'provstate',
            'provstate_other',
            'country',
            'postal_code',
            'phone',
            'company',
        ]);

        if ($values['provstate'] === 'other') {
            $values['provstate'] = null;
        }

        return $values;
    }

    protected function validate(): void
    {
        $provstate = $this->ui->getWidget('provstate');
        $country = $this->ui->getWidget('country');
        $postal_code = $this->ui->getWidget('postal_code');

        if ($country->value !== null) {
            $country_title = SwatDB::queryOne(
                $this->app->db,
                sprintf(
                    'select title from Country where id = %s',
                    $this->app->db->quote($country->value)
                )
            );
        } else {
            $country_title = null;
        }

        if ($provstate->value !== null && $provstate->value !== 'other') {
            // validate provstate by country
            $sql = sprintf(
                'select count(id) from ProvState
				where id = %s and country = %s',
                $this->app->db->quote($provstate->value, 'integer'),
                $this->app->db->quote($country->value, 'text')
            );

            $count = SwatDB::queryOne($this->app->db, $sql);

            if ($count == 0) {
                if ($country_title === null) {
                    $message_content = Store::_('The selected %s is ' .
                        'not a province or state of the selected country.');
                } else {
                    $message_content = sprintf(
                        Store::_('The selected ' .
                        '%%s is not a province or state of the selected ' .
                        'country %s%s%s.'),
                        '<strong>',
                        $country_title,
                        '</strong>'
                    );
                }

                $message = new SwatMessage($message_content, 'error');
                $message->content_type = 'text/xml';
                $provstate->addMessage($message);
            }
        }
    }

    // build phase

    protected function display()
    {
        parent::display();
        Swat::displayInlineJavaScript($this->getInlineJavaScript());
    }

    protected function buildInternal()
    {
        parent::buildInternal();

        $frame = $this->ui->getWidget('edit_frame');
        $frame->subtitle = $this->account->getFullName();

        $provstate_flydown = $this->ui->getWidget('provstate');
        $provstate_flydown->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'ProvState',
            'title',
            'id',
            'title'
        ));

        $provstate_other = $this->ui->getWidget('provstate_other');
        if ($provstate_other->visible) {
            $provstate_flydown->addDivider();
            $option = new SwatOption('other', 'Other…');
            $provstate_flydown->addOption($option);
        }

        $country_flydown = $this->ui->getWidget('country');
        $country_flydown->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'Country',
            'title',
            'id',
            'title'
        ));

        $form = $this->ui->getWidget('edit_form');
        $form->addHiddenField('account', $this->account->id);
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();
        $last_entry = $this->navbar->popEntry();
        $last_entry->title = sprintf(
            Store::_('%s Address'),
            $last_entry->title
        );

        $this->navbar->addEntry(new SwatNavBarEntry(
            $this->account->getFullName(),
            sprintf('Account/Details?id=%s', $this->account->id)
        ));

        $this->navbar->addEntry($last_entry);

        $this->title = $this->account->getFullName();
    }

    protected function loadDBData()
    {
        $row = SwatDB::queryRowFromTable(
            $this->app->db,
            'AccountAddress',
            $this->fields,
            'id',
            $this->id
        );

        if ($row === null) {
            throw new AdminNotFoundException(
                sprintf(
                    Store::_('account address with id ‘%s’ not found.'),
                    $this->id
                )
            );
        }

        $provstate_other = $this->ui->getWidget('provstate_other');
        if ($provstate_other->visible && $row->provstate === null) {
            $row->provstate = 'other';
        }

        $this->ui->setValues(get_object_vars($row));
    }

    protected function getInlineJavaScript()
    {
        $provstate = $this->ui->getWidget('provstate');
        $provstate_other_index = count($provstate->options);
        $id = 'account_address_page';

        return sprintf(
            "var %s_obj = new StoreAccountAddressEditPage('%s', %s);",
            $id,
            $id,
            $provstate_other_index
        );
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();
        $yui = new SwatYUI(['dom', 'event']);
        $this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/javascript/store-account-address-edit-page.js'
        );

        $yui = new SwatYUI(['dom', 'event']);
        $this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/javascript/store-account-address-edit-page.js'
        );
    }
}
