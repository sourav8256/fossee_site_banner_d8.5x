<?php
/**
 * @file
 * Contains \Drupal\fossee_site_banner\Controller\BannerController.
 */
namespace Drupal\fossee_site_banner\Controller;
class Banners {


    private $banner_url;
    private $default_db = "fossee_new.";

    public function banners(){

        $res_banner_dir = \Drupal::database()->select($this->default_db.'fossee_site_banner_variables','n')
            ->fields('n',array('value'))
            ->range(0,1)
            ->condition('n.name','banner_dir','=')
            ->execute()
            ->fetchCol();
        $this->banner_url= $res_banner_dir[0];


        $recent_time = 1*24*60*60; // D * H * MIN * SEC

        $result_recent = \Drupal::database()->select($this->default_db.'fossee_banner_details','n');
        $result_recent->fields('n',['id','file_name','status']);
        //->range(0,20)
        //->condition(db_or()->condition('n.created_timestamp',time()-$recent_time,'>')->condition('n.last_updated',time()-$recent_time))
        $result_recent->condition('n.last_updated',time()-$recent_time,">");
        $result_recent = $result_recent->execute()->fetchAllAssoc('file_name'); // returns the banners from the fossee_banner_details table

        $result_active = \Drupal::database()->select($this->default_db.'fossee_banner_details','n');
        $result_active->fields('n',['id','file_name','status']);
        //->range(0,20)
        //->condition(db_or()->condition('n.created_timestamp',time()-$recent_time,'>')->condition('n.last_updated',time()-$recent_time))
        $result_active->condition('n.status_bool',TRUE,"=");
        $result_active = $result_active->execute()->fetchAllAssoc('file_name'); // returns the banners from the fossee_banner_details table

        $result_inactive = \Drupal::database()->select($this->default_db.'fossee_banner_details','n');
        $result_inactive->fields('n',['id','file_name','status']);
        //->range(0,20)
        //->condition(db_or()->condition('n.created_timestamp',time()-$recent_time,'>')->condition('n.last_updated',time()-$recent_time))
        $result_inactive->condition('n.status_bool',FALSE,"=");
        $result_inactive = $result_inactive->execute()->fetchAllAssoc('file_name'); // returns the banners from the fossee_banner_details table


        $table_recent = $this->generateTable($result_recent,"Recent");
        $table_active = $this->generateTable($result_active,"Active");
        $table_inactive = $this->generateTable($result_inactive,"Inactive");


        $build['#attached']['library'][] = 'fossee_site_banner/banner-interface';

        $build['new_banner_link'] = array(
            '#markup' => '<a href=\'new-banner\'>Create New Banner</a><br><br>',
        );

        $build['table_recent'] = array(
            '#type' => 'inline_template',
            '#template' => $table_recent,
        );


        $build['table_active'] = array(
            '#type' => 'inline_template',
            '#template' => $table_active,
        );


        $build['table_inactive'] = array(
            '#type' => 'inline_template',
            '#template' => $table_inactive,
        );


        return $build;
    }


    /*
     * This function is used to generate tables of banners in different groups
     *
     */
    function generateTable($results,$table_title){

        //$dir = variable_get('fossee_site_banner_banner_directory', "not found");
        //$dir = "http://localhost/fossee/fossee_drupal/sites/default/files/site_banners/edited";
        $dir = $this->banner_url;
        //$path =  site_banners_path(); // path to the folder containing the banner files
        $realpath = file_create_url($dir); //
        $settings_form_link = $path =  "http://".$_SERVER['SERVER_NAME'] . base_path() . 'fossee-site-banner/banner-settings/';
        $col_length = 3; // The number of columns in the table in which banners are displayed
        $i=0;
        $banners = array();


        foreach ($results as $result) { // this foreach statement loops throught the result containing fossee_banner_details returned from the fossee_banner_details table in database
            # code...

            $banner = new \stdClass();
            $banner->file_name = $result->file_name;
            $banner->status = $result->status;
            $banner->id = $result->id;

            $banners[($i/$col_length)][($i%$col_length)] = $banner;
            unset($banner);
            $i++;
        }

        for($j=0;$j<sizeof($banners);$j++){ // loops through rows of banner images for checking necessary conditons and rendered accordingly

            unset($col); // col array contains the html code for displaing the banner images

            $col[0] = " ";
            $col[1] = " ";
            $col[2] = " ";

            for($k=0;$k<sizeof($banners[$j]);$k++){ // loops through columns of $j row of the banners array
                unset($banner);
                $banner = $banners[$j][$k];

                if($banners[$j]!=NULL){ // necessary for rows which contains less than 3 banners
                    if($banner->status == 'active'){
                        $status_pointer = "border: 10px solid orange"; // this style adds the orange border for displaying the active banners
                        $href = "set-banner-inactive/".$banner->id;
                    } else {
                        $status_pointer = "";
                        $href = "set-banner-active/".$banner->id;
                    }


                    $col[$k] = '
			<div style = "margin-left:auto;margin-right:auto;height : 170px; width : 200px;">
			<a href="'.$href.'">
			<img class="imgStyle" onclick="imageClicked(this); return false;" id="'.$banner->id.'" src="'.$realpath."/".$banner->file_name.'" style="height:60%;width: 100%;'.$status_pointer.'">
			</a>
			<button type="button" style = "width : 100%" class="btn" onclick="window.open(\''.$settings_form_link.$banner->id.'\',\'_self\')">Banner Settings</button>
			</div>
			';

                } else {
                    # code...
                    $col[$k] = "
                        <div style = \"margin-left:auto;margin-right:auto;height : 170px; width : 200px;\">
			            </div>
                     "; // if there is no image this element of col is left empty
                }
            }


            /* the html code for displaying banners is put into rows array which will be later used for rendering the table*/
            $rows[] = array(
                $col[0],
                $col[1],
                $col[2],

            );
        }

        /* checks that the $rows is not empty and renders the table */
        if (!empty($rows)) {

            try {
                $header = array($table_title,"","");
                //$output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'sort-table')));
                //$output .= theme('pager');


                $build['table1'] = array(
                    '#markup' => t('List of All locations')
                );

                $build['table1']['location_table'] = array(
                    '#theme' => 'table',
                    '#header' => $header,
                    '#rows' => $rows,
                );
                $build['table1']['pager'] = array(
                    '#type' => 'pager'
                );

                return $this->bootstrap_table_format($header,$rows);
                //return $build;
            } catch (Exception $e) {
            }


        } else {

            //$output .= t("No results found.");

        }

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




    /*
     * This function returns all the available banner files from database table fossee_banner_details
     * used in function fossee_site_banner_banners
     */

    function getFilesArray(){
        $result = db_select('fossee_banner_details','n')
            ->fields('n',array('id','file_name','status'))
            //->range(0,20)
            //->condition('n.status_bool',1,'=')
            ->execute();

        return $result;
    }






    /*
     * This function sets javascript and css code
     *
     */

    function setFrontend(){

        $base_url = "http://".$_SERVER['SERVER_NAME'] . base_path();
        $res = getFilesArray();

        $style = ".imgStyle
	{
		border : 10px solid orange;
	}";

        $jquery = 'https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js';
        drupal_add_js($jquery);
        $bannersArray = "";

        foreach ($res as $resu) {
            # code...
            $bannersArray =$bannersArray."'".$resu->id."'"." : "."'".$resu->status."'"." , ";
        }

        /* The following is the javascript to handle ajax calls to set the banners active and inactive */

        $js="
  //var bannersArray = new Array();
	var bannersArray = {".$bannersArray."};
	console.log(bannersArray);

	(function($) {
		$.fn.myJavascriptFunction = function(data) {
			alert(data);
		};
	})(jQuery);

	function imageClicked(param){
		console.log(bannersArray);
		changeCaller(param);
	}

	function changeCaller(param) {

		if(bannersArray[param.id] == \"active\"){
			setBannerInctiveAjaxCall(param);
		} else {
			setBannerActiveAjaxCall(param);
		}

	}

	function setBannerActiveAjaxCall(param){
		$.get(\"".$base_url."fossee-site-banner/set-banner-active/\"+param.id+'/arg'+Math.random()).success(function(data){ 
			if(data.result == 'success'){
				setBannerSelected(data.data);
			}

		});

	}

	function setBannerSelected(id){
	  onSuccess();
		//document.getElementById(id).style.border = \"10px solid orange\";
		//bannersArray[id] = \"active\";
	}

	function setBannerInctiveAjaxCall(param){
		$.get(\"".$base_url."fossee-site-banner/set-banner-inactive/\"+param.id+'/arg'+Math.random()).success(function(data){ 
			if(data.result == 'success'){
				setBannerUnselected(data.data);
			}
		});

	}

	function setBannerUnselected(id){
	  onSuccess();
		//document.getElementById(id).style.border = \"0px solid orange\";
		//bannersArray[id] = \"inactive\";
	}

  function onSuccess(){
    console.log('Onsuccess called');
    location.reload();
  }

	function changeImage(param) {
		if(bannersArray[param.id] == \"active\"){
			param.style.border = \"0px solid orange\";
			bannersArray[param.id] = \"inactive\";
		} else {
			param.style.border = \"10px solid orange\";
			bannersArray[param.id] = \"active\";
		}
	}

	";

        drupal_add_js($js, 'inline', 'header');
    }





}


