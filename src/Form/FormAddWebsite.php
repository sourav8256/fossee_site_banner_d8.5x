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

class FormAddWebsite extends FormBase{

    //private $default_db = "fossee_new.";
    public $default_db = "";

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "form_add_website";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['websites_table'] = array(
            '#markup' => $this->generateWebsitesTable(),
        );

        /*  $form['website_name'] = array(
            '#type' => 'textfield',
            //'#required' => TRUE,
            '#title' => t('Website Domain : <br/>'),
            '#size' => 10,
            '#description' => t(''),
            //'#prefix' => '<br/><br/>',
            //'#suffix' => '<br/><br/>',
          );*/

        $form['ajax_container'] = array(
            '#type'   => 'fieldset',
            '#title'  => t(""),
            //'#weight' => $form['body']['#weight'] + 1,
            '#prefix' => '<div id="ajax_wrapper">',
            '#suffix' => '</div>'
        );

        //$num_checkboxes = !empty($form_state['values']['add_field']) ? $form_state['values']['add_field'] : 1;
        //$num = ++$form_state['values']['add_field'];


        if(empty($form_state->get('n_items'))){
            $form_state->set('n_items',1);
        }
        $num = $form_state->get('n_items');
        //drupal_set_message("num is ".$num);


        for ($i=0;$i<$num;$i++) {
            $form['ajax_container']['id'.$i] = array(
                '#type' => 'textfield',
                '#tree' => TRUE,
                //'#required' => TRUE,
                '#title' => t('Website Domain : <br/>'),
                '#size' => 10,
                '#description' => t(''),
            );
        }

        $form['ajax_container']['add_field'] = array(
            '#type'   => 'submit',
            '#value'  => t('Add new field'),
            '#submit' => array([$this,'my_module_ajax_add_field']),
            '#ajax'   => array(
                'callback'=> [$this,'my_module_ajax_callback'],
                'wrapper' => 'ajax_wrapper',
                'effect'  => 'fade',
            ),
            '#name' => 'website_field_add_button',
        );

        $form['ajax_container']['remove_field'] = array(
            '#type'   => 'submit',
            '#value'  => t('Remove a field'),
            '#submit' => array([$this,'my_module_ajax_remove_field']),
            '#ajax'   => array(
                'callback'=> [$this,'my_module_ajax_callback'],
                'wrapper' => 'ajax_wrapper',
                'effect'  => 'fade',
            ),
            '#name' => 'website_field_remove_button',
        );

        $form['upload_submit'] = array(
            '#type' => 'submit',
            '#value' => 'Submit',
        );

        $form['all_banners'] = array(
            '#type' => "inline_template",
            '#template' => '<button onclick="window.open(\'banners\',\'_self\'); return false;">All Banners</button>',
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {


        //drupal_set_message("message");
        //drupal_set_message("the field is ".$form_state['values']['id0']." and n_items is ");


        $no_of_fields = $form_state->get('n_items');
        //drupal_set_message("number of fields ".$no_of_fields);
        for($i=0;$i<$no_of_fields;$i++){
            $site_name = $form_state->getValue('id'.$i);

            $triggeringElement = $form_state->getTriggeringElement();

            if(($triggeringElement['#name'] == 'website_field_add_button') || ($triggeringElement['#name'] == 'website_field_remove_button')){continue;};
            //if(empty($site_name)){continue;}

            if (filter_var($site_name, FILTER_VALIDATE_URL)) {

            } else {

                if (filter_var("http://".$site_name, FILTER_VALIDATE_URL)) {

                } else {

                    $form_state->setErrorByName("id".$i,"Please Give A Valid Domain Name ! ".$site_name." is invalid");
                }

            }
        }


    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $no_of_fields = $form_state->get('n_items');
        //drupal_set_message("number of fields ".$no_of_fields);
        for($i=0;$i<$no_of_fields;$i++) {

            $site_name = $form_state->getValue('id'.$i);
            $parsed_url = parse_url($site_name);

            try {
                if (array_key_exists("host",$parsed_url)) {
                    $domain = $parsed_url['host'];
                } elseif (array_key_exists("path",$parsed_url)) {
                    $domain = $parsed_url['path'];
                }
            } catch ( Exception $e){

            }


            try {
                $db_insert = \Drupal::database()->insert($this->default_db.'fossee_website_index')
                    ->fields(array(
                        'site_name' => $domain,
                    ))
                    ->execute();
                drupal_set_message("Domain added : " . $domain);
            } catch (PDOException $e) {
                if ($e->errorInfo[1] === 1062) {
                    drupal_set_message("Error 1062 : The Website ".$site_name." Already Exists, Please Enter A Different Website!");
                }
            } catch (Exception $e) {
                drupal_set_message("Unknown Database Exception : Website Is Not Created");

            }
        }

    }



    /**
     * This function is called by add_field button to add new website field
     */
    public function my_module_ajax_add_field(array &$form, FormStateInterface $form_state) {
        $form_state->set('n_items',$form_state->get('n_items')+1);
        $form_state->setRebuild();
    }

    /**
     * this function is called by
     */
    public function my_module_ajax_remove_field(array &$form, FormStateInterface $form_state) {
        $form_state->set('n_items',$form_state->get('n_items')-1);
        $form_state->setRebuild();
    }

    public function my_module_ajax_callback(array &$form, FormStateInterface $form_state) {

        return $form['ajax_container'];
    }


    function generateWebsitesTable(){
        $base_url = "http://".$_SERVER['SERVER_NAME'] . base_path();
        $banners = \Drupal::database()->select($this->default_db.'fossee_website_index','n') // for fetching the banner filename
        ->fields('n',array('site_name','site_code'))
            ->execute();

        foreach ($banners as $banner){
            $col1 = $banner->site_name;
            $col2 = "<a href='".$base_url."fossee-site-banner/edit-website/".$banner->site_code."'>Edit</a>";
            $col3 = "<a href='".$base_url."fossee-site-banner/delete-website/".$banner->site_code."'>Delete</a>";
            $rows[] = array($col1,$col2,$col3);
            //$rows[] = array("key ","value ");
        }

        $header = array("Available Websites","","");

        $output =  $this->bootstrap_table_format($header,$rows);
        return $output;

    }


    //General Function to create table
    function bootstrap_table_format($headers, $rows) {
        $thead = "";
        $tbody = "";
        foreach ($headers as $header) {
            $thead .= "<th>{$header}</th>";
        }
        foreach ($rows as $row) {
            $tbody .= "<tr>";
            $i = 0;
            foreach ($row as $data) {
                $datalable = $headers[$i++];
                $tbody .= "<td data-label=" . $datalable . ">{$data}</td>";
                //echo $data;
            }
            $tbody .= "</tr>";
        }
        $table = "
      
                <table class='table table-bordered table-hover'>
                  <thead>{$thead}</thead>
                  <tbody>{$tbody}</tbody>
                </table>
      
            ";
        return $table;
    }


}