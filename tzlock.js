function tzlock_submit(formName, action)
{
  document.forms[formName].elements["action"].value = action;
  document.forms[formName].submit();
}