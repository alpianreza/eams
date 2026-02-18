 <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm">
   <div class="container-fluid d-flex align-items-center position-relative">

     <!-- LEFT: Sidebar Toggle + Back -->
     <div class="d-flex align-items-center gap-2">
       <button class="btn btn-link" data-lte-toggle="sidebar">
         <i class="bi bi-list"></i>
       </button>

       <?php if (!empty($backUrl)): ?>
         <a href="<?= $backUrl ?>" class="btn btn-sm btn-light border">
           <i class="fa-solid fa-left-long"></i>
         </a>
       <?php endif; ?>
     </div>

     <!-- CENTER: Title -->
     <div class="position-absolute start-50 translate-middle-x">
       <span class="fw-semibold fs-5">
         <?= esc($title ?? 'Dashboard') ?>
       </span>
     </div>


     <!-- RIGHT: User -->
     <?php
      $userPhoto = session()->get('photo');
      $photoUrl = (!empty($userPhoto) && file_exists(FCPATH . 'uploads/users/' . $userPhoto))
        ? base_url('uploads/users/' . $userPhoto)
        : 'https://ui-avatars.com/api/?name=' . urlencode(session()->get('name'));
      ?>

     <?php
      $notifCount = $notifCount ?? 0;
      $notifications = $notifications ?? [];
      ?>

     <ul class="navbar-nav ms-auto align-items-center">

       <!-- Notification Bell -->
       <li class="nav-item dropdown me-3">
         <a class="nav-link position-relative"
           data-bs-toggle="dropdown"
           href="#">

           <i class="bi bi-bell fs-5"></i>

           <?php if ($notifCount > 0): ?>
             <span class="position-absolute badge bg-danger rounded-pill"
               style="
        top: 2px;
        right: -4px;
        font-size: 9px;
        padding: 2px 5px;
      ">
               <?= $notifCount ?>
             </span>

           <?php endif; ?>

         </a>

         <ul class="dropdown-menu dropdown-menu-end shadow-sm">

           <li class="dropdown-header">
             <?= $notifCount ?> Notifikasi
           </li>

           <li>
             <hr class="dropdown-divider">
           </li>

           <?php if ($notifCount > 0): ?>
             <li>
               <a href="<?= base_url('home') ?>" class="dropdown-item small">
                 <i class="bi bi-clock text-warning me-2"></i>
                 <?= $notifCount ?> periode perlu perhatian
               </a>
             </li>
           <?php else: ?>
             <li>
               <span class="dropdown-item text-muted small">
                 Tidak ada notifikasi
               </span>
             </li>
           <?php endif; ?>

         </ul>

       </li>

       <li class="nav-item dropdown">
         <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
           data-bs-toggle="dropdown" href="#">
           <img class="rounded-circle"
             width="32"
             height="32"
             style="object-fit:cover;"
             src="<?= $photoUrl ?>">
         </a>

         <ul class="dropdown-menu dropdown-menu-end shadow">
           <li>
             <a class="dropdown-item" href="<?= base_url('settings') ?>">
               <i class="bi bi-gear me-2"></i> Settings
             </a>
           </li>
           <li>
             <hr class="dropdown-divider">
           </li>
           <li>
             <a class="dropdown-item text-danger" href="<?= base_url('logout') ?>">
               <i class="bi bi-box-arrow-right me-2"></i> Logout
             </a>
           </li>
         </ul>
       </li>
     </ul>

   </div>

 </nav>