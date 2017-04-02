<?php
/**
* 2017 Fabvla snc
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@fabvla.com so we can send you a copy immediately.
*
*
*  @author    Fabvla <support@fabvla.com>
*  @copyright  2017 Fabvla snc
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

// BankWire Module (PS < 1.7) or PS_WIREPAYMENT (PS > 1.6) have to be installed first

class InvoiceBankInfo extends Module
{

    protected $html = '';

    public function __construct()
    {
        $this->name = 'invoicebankinfo';
        $this->author = 'Fabvla';
        $this->tab = 'front_office_features';
        $this->need_instance = 0;
        $this->version = '1.0.2';
        $this->bootstrap = true;
        $this->_directory = dirname(__FILE__);
        $this->module_dep = 'ps_wirepayment';
        if (_PS_VERSION_ < '1.7') {
            $this->module_dep = 'bankwire';
        }
        $this->dependencies = array($this->module_dep);
        $this->displayName = $this->l('Invoice bank info');
        $this->description = $this->l('Prints wire transfer info on the invoice.');
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7.99.99');
        parent::__construct();
    }

    public function install()
    {

        if (!parent::install() ||
            !Configuration::updateValue('INVOICE_BANK_INFO', 'installed') ||
            !$this->registerHook('displayPDFInvoice') ||
            !Configuration::updateValue('INVOICE_BANK_INFO_ENABLED', false)) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('INVOICE_BANK_INFO') ||
            !Configuration::deleteByName('INVOICE_BANK_INFO_ENABLED')
        ) {
            return false;
        }
        return true;
    }

    // Our module hook is DisplayPDFInvoice
    
    public function hookDisplayPDFInvoice($params)
    {
        $bankWireAccount = Configuration::get('BANK_WIRE_OWNER');
        $bankWireDetails = Configuration::get('BANK_WIRE_DETAILS');
        $bankWireAddress = Configuration::get('BANK_WIRE_ADDRESS');
        $bankWireInfoEnabled = Configuration::get('INVOICE_BANK_INFO_ENABLED');
        $order = new Order($params['object']->id_order);
        $module_used = $order->module;

        if ($bankWireInfoEnabled && $module_used == $this->module_dep) {
               $this->smarty->assign(
                   array(
                       'bank_wire_account' => $bankWireAccount,
                       'bank_wire_details' => nl2br($bankWireDetails),
                       'bank_wire_address' => nl2br($bankWireAddress)
                   )
               );
            return $this->display(__FILE__, 'invoice_bank_details.tpl');
        }
    }
    
    public function getContent()
    {
        $output = null;
     
        if (Tools::isSubmit('submit'.$this->name)) {
            $invoice_info_enabled = Tools::getValue('INVOICE_BANK_INFO_ENABLED');
            if ($this->isNullOrEmptyString(Configuration::get('BANK_WIRE_DETAILS')) && $invoice_info_enabled == 1) {
                $output .= $this->displayError($this->l('Please complete bank wire details, in bank wire module'));
            } else {
                Configuration::updateValue('INVOICE_BANK_INFO_ENABLED', $invoice_info_enabled);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }
    
    // Function for basic field validation (present and neither empty nor only white space
    
    public function isNullOrEmptyString($question)
    {
        return (!isset($question) || trim($question)==='');
    }
    
    // Display configuration form in backend
    
    public function displayForm()
    {
        // Array FieldForm
        $fields_form = array();
        
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
         
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Print wire transfer info on the invoice'),
                    'name' => 'INVOICE_BANK_INFO_ENABLED',
                    'values' => array(
                        array(
                            'id' => 'enable_wiretransfer_info_1',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'enable_wiretransfer_info_0',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right')
                );
        $helper = new HelperForm();
         
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
         
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
         
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
         
        // Load current value
        $helper->fields_value['INVOICE_BANK_INFO_ENABLED'] = Configuration::get('INVOICE_BANK_INFO_ENABLED');
         
        return $helper->generateForm($fields_form);
    }
}
