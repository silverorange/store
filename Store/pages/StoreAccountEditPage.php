<?php

/**
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEditPage extends SiteAccountEditPage
{
    protected function getUiXml()
    {
        return __DIR__ . '/account-edit.xml';
    }

    private function getAdditionalFields()
    {
        $fields = [];

        if ($this->ui->hasWidget('phone')) {
            $fields[] = 'phone';
        }

        if ($this->ui->hasWidget('company')) {
            $fields[] = 'company';
        }

        return $fields;
    }

    // process phase

    protected function updateAccount(SwatForm $form)
    {
        parent::updateAccount($form);

        $fields = $this->getAdditionalFields();

        if (count($fields) > 0) {
            $this->assignUiValuesToObject($this->account, $fields);
        }
    }

    // build phase

    protected function load(SwatForm $form)
    {
        parent::load($form);

        $fields = $this->getAdditionalFields();

        if (count($fields) > 0) {
            $this->assignObjectValuesToUi($this->account, $fields);
        }
    }
}
