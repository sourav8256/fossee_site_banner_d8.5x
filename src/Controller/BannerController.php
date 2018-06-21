<?php
/**
 * @file
 * Contains \Drupal\fossee_site_banner\Controller\BannerController.
 */
namespace Drupal\fossee_site_banner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class BannerController extends ControllerBase{

    public $default_db = "fossee_new.";

    public function content() {
        return array(
            '#type' => 'markup',
            '#markup' => t('Hello, World!'),
        );
    }

    public function banners($arg){

        $array['form'] = array(
            '#type' => 'markup',
            '#markup' => t($arg),
        );

        return $array;
    }

    public function setBannerActive($id){

        $num_updated = \Drupal::database()->update($this->default_db.'fossee_banner_details')
            ->fields(array(
                'status' => 'active',
                'status_bool' => 1,
                'last_updated' => time(),
            ))
            ->condition('id', $id, '=')
            ->execute();

        if($num_updated){
            $result = 'success';
        } else {
            # code...
            $result = 'failed';
        }

        return $this->redirect("fossee_site_banner.banners");

        //drupal_json_output(array('status' => 0, 'data' => $id, 'result' => $result));

    }

    public function setBannerInactive($id){
        $num_updated = \Drupal::database()->update($this->default_db.'fossee_banner_details')
            ->fields(array(
                'status' => 'inactive',
                'status_bool' => 0,
                'last_updated' => time(),
            ))
            ->condition('id', $id, '=')
            ->execute();


        if($num_updated){
            $result = 'success';
        } else {
            # code...
            $result = 'failed';
        }

        return $this->redirect("fossee_site_banner.banners");
        //drupal_json_output(array('status' => 0, 'data' => $id,'result' => $result));
    }

    public function deleteBanner($id){


        $op_result = TRUE;

        /* fetching the filename of the banner file */
        $file_name = \Drupal::database()->select($this->default_db.'fossee_banner_details','n')
            ->fields('n',array('file_name'))
            ->range(0,1)
            ->condition('n.id',$id,'=')
            ->execute()
            ->fetchCol();


        /* deleting database entry in fossee_banner_details table */
        $delete_row = \Drupal::database()->delete($this->default_db.'fossee_banner_details')
            ->condition('id', $id, '=')
            ->execute();


        /* checking if database row was deleted */
        if(!$delete_row){
            $op_result = FALSE;
        }

        /* checking if file was deleted*/
        if(!file_unmanaged_delete(\Drupal::state()->get('fossee_site_banner_banner_directory', "not found")."/".$file_name[0])){
            $op_result = FALSE;
        }

        if($op_result){
            $result = 'success';
        } else {
            # code...
            $result = 'failed';
        }


        //return $this->redirect("fossee_site_banner.banners");
        //drupal_json_output(array('status' => 0, 'data' => $id, 'result' => $result));

        return new JsonResponse(array('status' => 0, 'data' => $id, 'result' => $result));
    }
}

