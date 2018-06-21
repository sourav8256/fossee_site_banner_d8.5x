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

class FormNewBanner extends FormBase{

    private $default_db = "fossee_new.";
    private $banner_url;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "form_new_website";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        //module_load_include('inc','fossee_site_banner','inc/db_schema');

        $form['upload_item'] = array(
            '#type' => 'file',
            '#title' => 'Upload File',
            '#description' => "Allowed file types: [".\Drupal::state()->get('fossee_site_banner_allowed_file_types', "Not Set")."] and Max. file size: ".\Drupal::state()->get('fossee_site_banner_max_file_size', "Not Set")/(1024*1024)." MBs",
        );

        $form['banner_name'] = array(
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('Banner Name : <br/>'),
            '#size' => 10,
            //'#prefix' => '<br/><br/>',
            '#suffix' => '',
        );

        $form['banner_href'] = array(
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('Banner Redirect Url : <br/>'),
            '#description' => t('The url to open when the banner is clicked'),
            '#size' => 10,
            //'#prefix' => '<br/><br/>',
            //'#suffix' => '<br/><br/>',
        );

        $form['date'] = array(
            '#title' => "End Date",
            '#description' => "Please choose the date when the banner stops displaying.",
            '#date_format' => 'Y-m-d',
            '#date_year_range' => '0:+1',
            '#type' => 'date',
            //'#weight'=>0,
            '#datepicker_options' => array('minDate' => 0),
            '#required' => TRUE,
        );


        $form['upload_submit'] = array(
            '#type' => 'submit',
            '#value' => 'Submit',
            '#prefix' => '',
            '#suffix' => '<br/><br/>',
        );


        $path =  "http://".$_SERVER['SERVER_NAME'] . base_path();

        $form['html'] = array(

            '#type' => 'button',
            '#value' => t('See Available Banners'),
            '#attributes' => array(
                'onclick' => 'window.open(\''.$path.'fossee-site-banner/banners'.'\',"_self"); return false;',
            ),

        );


        return $form;

    }



    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $end_date = strtotime($form_state->getValue('date')." 23:59:59"); // the date when the banner has to stop displaying
        $timestamp = strtotime($form_state->getValue('date')); // converts time string from form to timestamp to be saved in database
        if(time()>$timestamp){ //checking if banner validity is before today's date
            //form_set_error("date","Sorry! you can't give a date before today's date!");
        }



        /*
         * Validating banner redirect url using inbuilt php function filter_var
         * */
        $url = $form_state->getValue('banner_href');
        if (filter_var($url, FILTER_VALIDATE_URL)) {

        } else {
            $form_state->setErrorByName("banner_href","The Banner Redirect Url is invalid please give a valid url including http:// or https://");
        }

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){

        //module_load_include('inc','fossee_site_banner','inc/mail');
        global $user;

        $allowed_extensions = \Drupal::state()->get('fossee_site_banner_allowed_file_types', "Not Set");
        $allowed_extensions = str_replace(","," ",$allowed_extensions);

        $validators = array(
            'file_validate_size' => array(\Drupal::state()->get('fossee_site_banner_max_file_size', "Not Set")), // size restriction for banner image size
            'file_validate_extensions' => array($allowed_extensions) // allowed extensions of banner image
        );

        $location = \Drupal::state()->get('fossee_site_banner_banner_directory', "not found");
        $real_path = drupal_realpath($location);
        $banner_name = $form_state->getValue('banner_name'); // the name of the banner file
        $banner_href = $form_state->getValue('banner_href');
        $end_date = strtotime($form_state->getValue('date')." 23:59:59"); // the date when the banner has to stop displaying
        $created_timestamp = time();


        /*saving uploaded file to a temporary location*/
        $file = file_save_upload('upload_item', $validators, "temporary://"); // check validation from the $validators array and saves to a database with location

        //dpm($file[0]->destination);
        //dpm($file);


        if($file[0]->destination != NULL) { // check if the file passed the validation and was uploaded

            $new_filename = $this->generateNewFilename(basename($file[0]->destination),$created_timestamp);
            /* moving the file in temporary to the desired location and rename it to a filename_created_timestamp format */
            if(!rename(drupal_realpath($file[0]->destination), $real_path."/".$new_filename)){
                drupal_set_message("Banner Not Created : File Renaming Failed!","error");
                return;
            }




            try {
                $db_insert = \Drupal::database()->insert($this->default_db.'fossee_banner_details')
                    ->fields(array(
                        'banner_name' => $banner_name,
                        'file_name' => $new_filename,
                        'timestamp' => $end_date,
                        'created_timestamp' => $created_timestamp,
                        'last_updated' => $created_timestamp,
                        'banner_href' => $banner_href,
                    ))
                    ->execute();
            } catch (Exception $e) {
                drupal_set_message("Sorry Banner has not been created due to some unknown database error!".$e);
                return;
            }


            if($db_insert) {

                drupal_set_message("File Successfully Uploaded : " . $new_filename);
                drupal_set_message('Banner Name : ' . $banner_name);
                drupal_set_message('End Date : ' . date('Y-m-d H:i:s', $end_date));

                $params['banner_name'] = $banner_name;
                $params['to'] = \Drupal::state()->get('fossee_site_banner_banner_admin',NULL);
                //drupal_mail('fossee_site_banner','banner_created',$user->mail,language_default(),$params);

                return $this->redirect("fossee_site_banner.banners");

            } else {
                drupal_set_message("Sorry Banner Is Not Created For Some Reason!");
            }

        } else {
            //drupal_set_message("Sorry File Could Not Be Uploaded Please Select The Correct File Type And Size!");
        }

        return;
    }

    public function generateNewFilename($filename,$timestamp){
        $extension = pathinfo($filename,PATHINFO_EXTENSION);
        $new_filename = $filename."_".$timestamp.".".$extension;
        return $new_filename;
    }


}