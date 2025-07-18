<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('User Dashboard') ?>
<!-- ######################### [End-header part] ############################################## -->


<!-- ######################### [Start-body/user_dashboard part] ############################### -->
<div class="container-fluid mt-4">
  
<div class="main-content-header">
        <h2>Admin Dashboard</h2>
  </div>

  <div class="dashboard-vtab-container">
    <div id="tab-content">
        <div class="nav flex-column nav-pills" id="v-tabs" role="tablist" aria-orientation="vertical">
          <!-- Buttons will be inserted here dynamically -->
        </div>
      </div>
    </div>
  </div>

  <script>
    const tabs_btn_name = ['User Profile', 'Employee Info', 'Category Info', 'Sub Category Info', 'Product Info', 'Order Info', 'Report Info'];
    const tabs_relavent_file_name = ['user_profile', 'employee_info', 'category_info', 'subcategory_info', 'product_info', 'order_info', 'report_info'];
    const tabContainer = document.getElementById('v-tabs');

    tabs_btn_name.forEach((tab, index) => {
      const btn = document.createElement('button');
      btn.className = 'btn btn-primary mb-3';
      btn.textContent = `${tabs_btn_name[index]}`;
      btn.setAttribute('data-tab', tabs_btn_name);
      btn.id = `id_${tab}`;
      
      btn.addEventListener('click', () => {
        window.location.href = `index.php?page=${tabs_relavent_file_name[index]}`;
      });

      tabContainer.appendChild(btn);
    });
  </script>
<!-- ######################### [End-body/user_dashboard part] ################################# -->



<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->