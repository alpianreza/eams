<?php

namespace Config;

class PdfPermission
{
  /**
   * Role yang BOLEH print PDF
   * Tinggal tambah kalau ada role baru
   */
  public static array $allowedRoles = [
    'admin',
    // 'safety',
    // 'compliance',
    // 'auditor',
  ];
}
