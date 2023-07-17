
document.addEventListener("DOMContentLoaded",changeAllEnrolmentOptions);

function changeEnrolmentModeOptions(id){
  if (id>0) {
    enrolmentModeList = document.getElementById('menuenrolmentmode_'+id);
    withuserdatas = document.getElementsByName('withuserdatas_'+id)[0];
  } else {
    enrolmentModeList = document.getElementById('id_enrolmentmode');
    withuserdatas = document.getElementById('id_withuserdatas');
  }
  if (withuserdatas.checked) {
    enrolmentModeList.options[1].disabled = false;
  } else {
    enrolmentModeList.options[1].disabled = true;
  }
}

function changeAllEnrolmentOptions() {
  checkboxs = document.querySelectorAll('[name^="withuserdatas"]');
  checkboxs.forEach(check => {
    if(check.name.split('_').length > 1) {
      changeEnrolmentModeOptions(id = check.name.split('_')[1]);
    } else {
      changeEnrolmentModeOptions(-1);
    }
  });
}