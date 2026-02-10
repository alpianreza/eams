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

     <ul class="navbar-nav ms-auto">
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