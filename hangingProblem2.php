<?php

include_once('../init.php');

if(!user_access('common_monitor')) die();

if(strtoupper($_SERVER['REQUEST_METHOD'])==='POST' && !empty($_POST['user_id'])){

    $insertData = [];

    foreach($_POST['activity_id'] as $currActId) $insertData[] = ['uid'=>$_POST['user_id'],'activity_id'=>$currActId];

    $userJoinAct = new UserJoinActivity();

    $submitResult = $userJoinAct->bulkAdd($insertData);

    if($submitResult == true){
        echo 'Submit succeed';
    }elseif($submitResult == false){
        echo 'Unexpected error,please contact admin or try again later.';
    }else{
        echo $editResult;
    }
}

//fetch all role
$roleQuery = "SELECT `rid`,`name` FROM `role`;";
$roleList = db_query($roleQuery)->fetchAll();
$roleSelection = HtmlBuilder::options($roleList,'rid','name');

$classQuery = "SELECT `field_class_value` AS `classes` FROM cymcass.field_data_field_class group by `field_class_value`;";
$classList = db_query($classQuery)->fetchAll();
$classSelection = HtmlBuilder::options($classList,'classes');

$activityQuery = "SELECT `tid`,`name` FROM `taxonomy_term_data` WHERE vid = 7;";
$activityList = db_query($activityQuery)->fetchAll();
$activitySelection = HtmlBuilder::checkbox($activityList,'activity_id[]','tid','name');

?>

<!DOCTYPE html>
<head>

<?php include(Config::get('root_document').'/includes/common/includeHeader.php'); ?>
<?php include(Config::get('root_document').'/includes/common/includeCSS.php'); ?>

<link rel="stylesheet" type="text/css" href="<?php echo Config::get('http_root'); ?>/includes/css/menubar.css"> 

<style>

.container {
    font-size:2rem;
}

form {
    margin-top:2rem;
}

.form-group>span {
    display:inline-block;
    min-width:10rem;
}

.form-group select {
    min-width:25rem;
}

.hints {
    color: #989898;
}

.btn-success  {
    font-size:2.5rem;
}

.activites-group {
    /* float:right; */
    height: 80vh;
    padding: .5em 2em;
}

.multi-select-container {
    height:100%;
    overflow:scroll;
}


</style>

</head>
<body>

<?php include(Config::get('root_document').'/includes/common/menuBar.php'); ?>

<div class="container">

    <h2>User Join Activity</h2>

    <p class='hints'>Select one user and multiple activities.</p>

    <form id='user_join_act_form' action='' method='POST'>
        <div class='row'>
            <div class='col-md-4'>
                <div class='form-group'>
                <span>User role</span>
                <select id='role_selection'>
                    <option value='default'>Default</option>
                    <?php echo $roleSelection; ?>
                </select>
                </div>
                <div class='form-group'>
                <span>Class name</span>
                <select id='class_selection'>
                    <option value='default'>Default</option>
                    <?php echo $classSelection; ?>
                </select>
                </div>
                <div class='form-group'>
                <span>User name</span>
                <select name='user_id' id='user_selection'>
                    <option></option>
                </select>
                </div>
                <input type='submit' class='btn btn-success' value='Submit'/>
            </div>
            
            <div class='col-md-6'>
                <div class='form-group activites-group'>
                <p>Activity:</p>
                    <div class='multi-select-container'>
                        <?php echo $activitySelection; ?>
                    </div>
                </div>
            </div>


        </div>
    </form>

</div>

</body>

<?php include(Config::get('root_document').'/includes/common/includeJS.php'); ?>

<script>

    var roleSelection = document.querySelector('#role_selection');

    var classSelection = document.querySelector('#class_selection');

    var userSelection = document.querySelector('#user_selection');

    // var activityInput = document.querySelector('input[name=activity_id]');

    var submitBtn = document.querySelector('#user_join_act_form input[type=submit]');

    $( "#class_selection" ).parent().hide();

    roleSelection.addEventListener('change',function(ev){
        switch (ev.target.options[ev.target.selectedIndex].innerText.toLowerCase()) {
            case 'student': 
                $( "#class_selection" ).parent().show( "fold" )
                while(userSelection.firstChild) userSelection.removeChild(userSelection.firstChild);
            break;
            default: 
                $( "#class_selection" ).parent().hide("fold");
                classSelection.selectedIndex = '0'; 
                fetchUsers();
            ;
        }
    });

    classSelection.addEventListener('change',function(ev){
        if(ev.target.value != 'default') fetchUsers();
    });

    document.querySelector('#user_join_act_form').addEventListener('submit',function(ev){
        if(!userSelection.value||document.querySelector("input[name='activity_id[]']:checked") == null){
            ev.preventDefault();
            alert('Requested field empty.');
        }
    })

    function optionBuilder(select, source, value = null, display = null, attr = null){
        
        display = display == null ? value : display;
        select = typeof select == 'string' ? document.querySelector(select) : select ;
        var result = '',
        htmlAttr = null;
        var tempOpt = {};

        if(value != null){
            Object.keys(source).forEach(function(key){
                tempOpt = document.createElement('option');
                tempOpt.value = source[key][value];
                tempOpt.innerText = source[key][display];
                if(attr != null){
                    Object.keys(attr).forEach(function(attrKey){
                        tempOpt.setAttribute('data-'+attrKey,source[key][attr[attrKey]]);
                    });
                }
                select.appendChild(tempOpt);
            });
        return true;
        }

        Object.keys(source).forEach(function(key){
            tempOpt = document.createElement('option');
            tempOpt.value = source[key];
            tempOpt.innerText = source[key];
            select.appendChild(tempOpt);
        });
        return true;
    }

    function fetchUsers(){
        var formData = new FormData();

        if(roleSelection.value != 'default') formData.append('rid',roleSelection.value);
        if(classSelection.style.display != 'none' && classSelection.value != 'default') formData.append('class',classSelection.value);

        while(userSelection.firstChild) userSelection.removeChild(userSelection.firstChild);

        submitBtn.disabled = true;

        $.ajax({
            url: window.location.protocol+'//'+window.location.hostname+"/ajaxFetch.php?action=fetch_smartcard_users",
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            dataType: 'json',
            success: function(response){
                
                submitBtn.disabled = false;

                try{
                    optionBuilder(userSelection, response, 'uid', 'eng_name');
                    return true;
                }catch(e){
                    console.log(e.message);
                    return false;
                }
            }
        })
    }

</script>
