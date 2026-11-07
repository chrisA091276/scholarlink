@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const errors=@json($errors->messages());
  document.querySelectorAll('input[name],select[name],textarea[name]').forEach((field,index)=>{
    if(field.closest('#applicationWizard'))return;
    const key=field.name.replace(/\[([^\]]*)\]/g,'.$1').replace(/\.$/,'');
    if(!errors[key])return;
    if(!field.id)field.id='form-field-'+index;
    if(document.getElementById(field.id+'-error'))return;
    const label=field.closest('.col-md-6,.col-md-4,.mb-3,.form-check')?.querySelector('label');if(label&&!label.htmlFor)label.htmlFor=field.id;
    field.classList.add('is-invalid');field.setAttribute('aria-invalid','true');
    const message=document.createElement('div');message.className='invalid-feedback d-block';message.id=field.id+'-error';message.textContent=errors[key].join(' ');
    field.setAttribute('aria-describedby',message.id);field.after(message);
  });
});
</script>
@endif
