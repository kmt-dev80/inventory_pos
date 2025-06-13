    <footer class="footer">
        <div class="container-fluid d-flex justify-content-between">
          <nav class="pull-left">
            <ul class="nav">
              <li class="nav-item">
                <a class="nav-link" href="http://localhost/inventory_pos/">
                  Inventory-pos
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#"> Help </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#"> Licenses </a>
              </li>
            </ul>
          </nav>
          <div class="copyright">
            © <?= date('Y') ?> POS System made with <i class="fa fa-heart heart text-danger"></i> by
            <a href="https://github.com/kmt-dev80?tab=repositories">kmt_dev80 </a>&<a href="https://github.com/ImtiazAhmedArefin"> ImtiazAhmed
          </div>
          <div>
            Distributed by
            <a target="_blank" href="http://localhost/inventory_pos/">WDPF64</a>.
          </div>
        </div>
    </footer>
  </div>

  <!-- this is just a demo -->
  <div class="custom-template">
    <div class="title">Settings</div>
    <div class="custom-content">
      <div class="switcher">
        <div class="switch-block">
          <h4>Logo Header</h4>
          <div class="btnSwitch">
            <button
              type="button"
              class="selected changeLogoHeaderColor"
              data-color="dark"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="blue"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="purple"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="light-blue"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="green"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="orange"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="red"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="white"
            ></button>
            <br />
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="dark2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="blue2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="purple2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="light-blue2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="green2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="orange2"
            ></button>
            <button
              type="button"
              class="changeLogoHeaderColor"
              data-color="red2"
            ></button>
          </div>
        </div>
        <div class="switch-block">
          <h4>Navbar Header</h4>
          <div class="btnSwitch">
            <button
              type="button"
              class="changeTopBarColor"
              data-color="dark"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="blue"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="purple"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="light-blue"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="green"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="orange"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="red"
            ></button>
            <button
              type="button"
              class="selected changeTopBarColor"
              data-color="white"
            ></button>
            <br />
            <button
              type="button"
              class="changeTopBarColor"
              data-color="dark2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="blue2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="purple2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="light-blue2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="green2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="orange2"
            ></button>
            <button
              type="button"
              class="changeTopBarColor"
              data-color="red2"
            ></button>
          </div>
        </div>
        <div class="switch-block">
          <h4>Sidebar</h4>
          <div class="btnSwitch">
            <button
              type="button"
              class="changeSideBarColor"
              data-color="white"
            ></button>
            <button
              type="button"
              class="selected changeSideBarColor"
              data-color="dark"
            ></button>
            <button
              type="button"
              class="changeSideBarColor"
              data-color="dark2"
            ></button>
          </div>
        </div>
      </div>
    </div>
    <div class="custom-toggle">
      <i class="icon-settings"></i>
    </div>
  </div>
</div>
<!--   Core JS Files   -->
<script src="<?= BASE_URL ?>assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

<!-- jQuery Scrollbar -->
<script src="<?= BASE_URL ?>assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

<!-- Chart JS -->
<script src="<?= BASE_URL ?>assets/js/plugin/chart.js/chart.min.js"></script>

<!-- jQuery Sparkline -->
<script src="<?= BASE_URL ?>assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

<!-- Chart Circle -->
<script src="<?= BASE_URL ?>assets/js/plugin/chart-circle/circles.min.js"></script>

<!-- Datatables -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.js"></script>
<!-- After jQuery and DataTables JS -->
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- Bootstrap Notify -->
<script src="<?= BASE_URL ?>assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Kaiadmin JS -->
<script src="<?= BASE_URL ?>assets/js/kaiadmin.min.js"></script>


<script src="<?= BASE_URL ?>assets/js/theme-persistence.js"></script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>

<script>
  $(document).ready(function() {
    ['#productTable', '#stockTable', '#purchasesTable', '#returnsTable', '#salesTable', '#salesReport', '#salesReturnsTable'].forEach(function(id) {
        $(id).DataTable({
            dom: 'lBfrtip',
            buttons: ['csv', 'excel', 'pdf', 'print']
        });
    });
    $('#trashTable').DataTable();
    $('#brandTable').DataTable();
    $('#supplierTable').DataTable();
    $('#customerTable').DataTable();
});

        
  $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
    type: "line",
    height: "70",
    width: "100%",
    lineWidth: "2",
    lineColor: "#177dff",
    fillColor: "rgba(23, 125, 255, 0.14)",
  });

  $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
    type: "line",
    height: "70",
    width: "100%",
    lineWidth: "2",
    lineColor: "#f3545d",
    fillColor: "rgba(243, 84, 93, .14)",
  });

  $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
    type: "line",
    height: "70",
    width: "100%",
    lineWidth: "2",
    lineColor: "#ffa534",
    fillColor: "rgba(255, 165, 52, .14)",
  });
</script>
</body>
</html>