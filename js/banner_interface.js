//var bannersArray = new Array();
var bannersArray = {'2' : 'inactive' , '16' : 'inactive' , '17' : 'inactive' , '18' : 'inactive' , '19' : 'active' , '20' : 'inactive' , };
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

    if(bannersArray[param.id] === "active"){
        setBannerInctiveAjaxCall(param);
    } else {
        setBannerActiveAjaxCall(param);
    }

}

function setBannerActiveAjaxCall(param){
    $.get("http://localhost/fossee/fossee_drupal/fossee-site-banner/set-banner-active/"+param.id+'/arg'+Math.random()).success(function(data){
        if(data.result === 'success'){
            setBannerSelected(data.data);
        }

    });

}

function setBannerSelected(id){
    onSuccess();
    //document.getElementById(id).style.border = "10px solid orange";
    //bannersArray[id] = "active";
}

function setBannerInctiveAjaxCall(param){
    $.get("http://localhost/fossee/fossee_drupal/fossee-site-banner/set-banner-inactive/"+param.id+'/arg'+Math.random()).success(function(data){
        if(data.result === 'success'){
            setBannerUnselected(data.data);
        }
    });

}

function setBannerUnselected(id){
    onSuccess();
    //document.getElementById(id).style.border = "0px solid orange";
    //bannersArray[id] = "inactive";
}

function onSuccess(){
    console.log('Onsuccess called');
    location.reload();
}

function changeImage(param) {
    if(bannersArray[param.id] === "active"){
        param.style.border = "0px solid orange";
        bannersArray[param.id] = "inactive";
    } else {
        param.style.border = "10px solid orange";
        bannersArray[param.id] = "active";
    }
}
