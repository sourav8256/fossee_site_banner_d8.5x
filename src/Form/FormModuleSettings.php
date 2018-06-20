<?php
/**
 * Created by PhpStorm.
 * User: sourav
 * Date: 20/6/18
 * Time: 3:03 PM
 */

namespace Drupal\fossee_site_banner\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

class FormModuleSettings extends FormBase{

    private $default_db = "fossee_new";
    private $banner_url;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "form_module_settings";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        //module_load_include('inc','fossee_site_banner','inc/db_schema');

        $form['banner_admin'] = array(
            '#type' => 'textfield',
            '#title' => t('Email of the Banner administrator'),
            '#description' => t('Necessary emails related to banners will be sent to the email.'),
            '#size' => 50,
            '#maxlength' => 255,
            '#default_value' => \Drupal::state()->get('fossee_site_banner_banner_admin', ''),
        );

        $form['file_size'] = array(
            '#type' => 'textfield',
            '#title' => t('Maximum file size allowed for upload (in MBs)'),
            '#description' => t('The maximum file size allowed for upload'),
            '#element_validate' => array("element_validate_number"),
            '#size' => 50,
            '#maxlength' => 255,
            '#default_value' => \Drupal::state()->get('fossee_site_banner_max_file_size', '')/(1024 * 1024),
        );
        $form['extensions'] = array(
            '#type' => 'textfield',
            '#title' => t('Allowed image file extensions'),
            '#description' => t('A space separated list of source file extensions that are permitted to be uploaded on the server'),
            '#size' => 50,
            '#maxlength' => 255,
            '#default_value' => \Drupal::state()->get('fossee_site_banner_allowed_file_types', ''),
        );
        $form['banner_dir'] = array(
            '#type' => 'textfield',
            '#title' => t('Banner Directory'),
            '#description' => t('Location where all banner images will be stored'),
            '#size' => 50,
            '#maxlength' => 255,
            '#default_value' => \Drupal::state()->get('fossee_site_banner_banner_directory', ''),
        );

        try {
            $res_banner_dir = \Drupal::database()->select('fossee_new.fossee_site_banner_variables', 'n')
                ->fields('n', array('value'))
                ->range(0, 1)
                ->condition('n.name', 'banner_dir', '=')
                ->execute()
                ->fetchCol();
        } catch (Exception $e) {
            drupal_set_message("'fossee_site_banner_variables' table not found please update the tables","error");
        }
        $banner_folder = $res_banner_dir[0];

        $form['banner_url'] = array(
            '#type' => 'textfield',
            '#title' => t('Banner Directory Url(without trailing "/")'),
            '#description' => t('Url to the directory where banners are stored(without trailing "/")'),
            '#size' => 50,
            '#maxlength' => 255,
            //'#required' => TRUE,
            '#default_value' => $banner_folder,
        );

        $form['update_tables'] = array(
            '#type' => 'submit',
            '#value' => t('Update Tables'),
            '#name' => t('update_tables'),
            '#suffix' => t('<br><br>'),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Submit',
            '#name' => t('submit_button'),
        );

        return $form;
    }



    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $form_state->getValue('banner_dir');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){

        $triggeringElement = $form_state->getTriggeringElement();

        if ($triggeringElement['#name'] === "update_tables") {

            $schema = get_schema();
            //dpm($schema);

            if (!db_table_exists("fossee_banner_details")) {
                db_create_table("fossee_banner_details", $schema['fossee_banner_details']);
            }

            if (!db_table_exists("fossee_website_index")) {
                db_create_table("fossee_website_index", $schema['fossee_website_index']);
            }

            if (!db_table_exists("fossee_site_banner_variables")) {
                db_create_table("fossee_site_banner_variables", $schema['fossee_site_banner_variables']);
            }

            drupal_set_message("All tables are updated");

            return;
        }


        $banner_dir = $form_state->getValue('banner_dir');
        $banner_url = $form_state->getValue('banner_url');

        \Drupal::state()->set('fossee_site_banner_banner_admin', $form_state->getValue('banner_admin'));
        \Drupal::state()->set('fossee_site_banner_max_file_size', $form_state->getValue('file_size') * 1024 * 1024);
        \Drupal::state()->set('fossee_site_banner_allowed_file_types', $form_state->getValue('extensions'));
        drupal_set_message(t('Settings updated'), 'status');
        if (!is_dir($banner_dir)) {
            if (drupal_mkdir($banner_dir, NULL, TRUE, NULL)) {
                \Drupal::state()->set('fossee_site_banner_banner_directory', $form_state->getValue('banner_dir'));
            } else {
                drupal_set_message(t("Failure : could not create directory"), "error");
            }
        } else {
            \Drupal::state()->set('fossee_site_banner_banner_directory', $form_state->getValue('banner_dir'));
        }

        $db_result = \Drupal::database()->select($this->default_db.".fossee_site_banner_variables", "n")
            ->fields('n')
            ->execute();

        $db_result->allowRowCount = TRUE;

        $rows = $db_result->rowCount();

        if ($rows >= 1) {

            $db_update = \Drupal::database()->update($this->default_db .'.fossee_site_banner_variables')
                ->fields(array(
                    'value' => $banner_url,
                ))
                ->condition("name", "banner_dir", "=")
                ->execute();
        } else {

            $db_insert = \Drupal::database()->insert($this->default_db .'.fossee_site_banner_variables')
                ->fields(array(
                    'name' => 'banner_dir',
                    'value' => $banner_url,
                ))
                ->execute();
        }

        return;
    }

}