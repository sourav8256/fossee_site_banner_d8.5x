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

class SiteBannerSettings extends FormBase{

    //private $default_db = "fossee_new.";
    public $default_db = "";
    private $banner_url;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "form_banner_settings";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state,$arg_banner_id = NULL) {

        $res_banner_dir = \Drupal::database()->select($this->default_db.'fossee_site_banner_variables','n')
            ->fields('n',array('value'))
            ->range(0,1)
            ->condition('n.name','banner_dir','=')
            ->execute()
            ->fetchCol();
        $this->banner_url= $res_banner_dir[0];


        $base_url =  "http://".$_SERVER['SERVER_NAME'] . base_path();

        //drupal_add_js(array('fossee_site_banner' => array('id' => $arg_banner_id, 'base_url' => $base_url)), array('type' => 'setting'));
        //drupal_add_js(drupal_get_path("module","fossee_site_banner")."/js/main.js");
        //$banner_dir = "public://site_banners/";
        $banner_dir = $this->banner_url;

        $realpath = file_create_url($banner_dir);


        $result = $this->getSitesList(); // fetches the list of sites from fossee_website_index table

        /* getting the list of allowed sites saved as json */

        $allowed_site_json = \Drupal::database()->select($this->default_db.'fossee_banner_details','n')
            ->fields('n',array('allowed_sites'))
            ->range(0,1)
            ->condition('n.id',$arg_banner_id,'=')
            ->execute()
            ->fetchCol(); // fetches first column of teh result

        $banner_filename = \Drupal::database()->select($this->default_db.'fossee_banner_details','n') // for fetching the banner filename
        ->fields('n',array('file_name'))
            ->range(0,1)
            ->condition('n.id',$arg_banner_id,'=')
            ->execute()
            ->fetchCol();

        $allowed_sites = json_decode($allowed_site_json[0],true); // receives a json string containing allowed site codes and decodes it into an array

        foreach ($result as $res) {
            # code...
            $sites[$res->site_code] = t($res->site_name); // gets names of the sites from $result to be displayed in the form
        }



        $form['#attached']['library'][] = 'fossee_site_banner/main-js';
        $form['#attached']['drupalSettings']['base_url'] = $base_url;

        $form['banner'] = array(
            "#prefix" => '<img src="'.$realpath.'/'.$banner_filename[0].'">',
        );


        $form['sites'] = array( // renders chckboxes with corresponding list of sites that the user can select to include in the list of allowed sites
            '#title' => t('Allowed Sites'),
            '#type' => 'checkboxes',
            '#description' => t('Select the sites you would like to display this  banner on.'),
            '#options' => $sites,
            '#default_value' => $allowed_sites,
        );


        $form['edit_banner'] = array(
            '#markup' => '<a href="../edit-banner/'.$arg_banner_id.'">Edit Banner</a><br><br>',
        );

        $form['add_new_website'] = array(
            '#prefix' => "<a href='../add-website'>Add New Websites</a>",
        );

        $form['banner_id'] = array( // hidden field that stores the banner id received from the argument
            '#type' => 'hidden',
            '#value' => $arg_banner_id,
        );

        $form['submit'] = array( // submit
            '#type' => 'submit',
            '#value' => 'Submit Form',
            '#prefix' => '<br/><br/>'
        );

        $form['delete'] = array(
            '#type' => "inline_template",
            '#template' => "<input type='button' onclick='deleteBanner(".$arg_banner_id."); return false;' value='Delete Banner' />&nbsp;&nbsp;",
        );


        $form['back'] = array(
            '#type' => "inline_template",
            '#template' => str_replace("url",$base_url."/fossee-site-banner/banners","<button onclick='window.open(\"url\",\"_self\"); return false;' >Go Back</button>"),
        );

        return $form;

    }


    /*
 * This function returns the lists of indexed sites from the table website index for use in function banner_settings_form
 *
 */

    function getSitesList(){
        $result = \Drupal::database()->select($this->default_db.'fossee_website_index','n')
            ->fields('n',array('site_code','site_name'))
            ->range(0,50)
            //->condition('n.uid',$uid,'=')
            ->execute();

        return $result;

    }



    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {


    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {


        $banner_id = $form_state->getValue('banner_id'); // gets the banner id from the form

        $allowed_site_json = json_encode(array_filter($form_state->getValue('sites'))); // converts array into json


        $num_updated = \Drupal::database()->update($this->default_db.'fossee_banner_details')
            ->fields(array(
                'allowed_sites' => $allowed_site_json, // field in fossee_banner_details table containing list of allowed sites as json string
            ))
            ->condition('id', $banner_id, '=') // matches the banner id
            ->execute();

        return $this->redirect("fossee_site_banner.banners");

    }


}