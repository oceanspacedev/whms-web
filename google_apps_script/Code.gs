/**
 * WMS - Warehouse & Logistics Management System
 * Complete Selular / Ocean Space
 * 
 * Google Apps Script Webhook
 * Menangani penambahan baris pengiriman dari CSA WMS ke masing-masing sheet cabang
 * dan mencegah duplikasi Nomor Surat Jalan (SJ).
 * 
 * PANDUAN DEPLOY:
 * 1. Buka Google Spreadsheet target (https://docs.google.com/spreadsheets/d/1AQ7h3_bPRpCcCy5CZav7vfe1p_flt7mWRLUr0byw1a0/edit)
 * 2. Klik menu: Extensions (Ekstensi) -> Apps Script
 * 3. Hapus kode default dan paste kode ini
 * 4. Klik tombol "Deploy" (Terapkan) -> "New deployment" (Penerapan baru)
 * 5. Pilih type: "Web app" (Aplikasi web)
 * 6. Set Description: "WMS Webhook"
 * 7. Set Execute as: "Me" (Saya)
 * 8. Set Who has access: "Anyone" (Siapa saja)
 * 9. Klik "Deploy", izinkan hak akses (Authorize access), lalu salin "Web app URL"
 * 10. Simpan URL tersebut di file .env (GOOGLE_SHEET_WEBHOOK_URL) atau form WMS Filament.
 */

function doPost(e) {
  var lock = LockService.getScriptLock();
  // Tunggu lock maksimal 30 detik agar aman jika ada request bersamaan
  if (!lock.tryLock(30000)) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error",
      message: "Server sheet sedang sibuk, silakan coba beberapa saat lagi."
    })).setMimeType(ContentService.MimeType.JSON);
  }

  try {
    if (!e || !e.postData || !e.postData.contents) {
      return ContentService.createTextOutput(JSON.stringify({
        status: "error",
        message: "No POST body received."
      })).setMimeType(ContentService.MimeType.JSON);
    }

    var payload = JSON.parse(e.postData.contents);
    var sheetName = payload.sheetName;
    var rows = payload.rows; // Array baris (25 kolom per baris)

    if (!sheetName || !rows || !rows.length) {
      return ContentService.createTextOutput(JSON.stringify({
        status: "error",
        message: "Parameter sheetName atau rows kosong."
      })).setMimeType(ContentService.MimeType.JSON);
    }

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);

    // Buat sheet baru otomatis jika tab belum ada
    if (!sheet) {
      sheet = ss.insertSheet(sheetName);
      // Buat header standar 25 kolom jika sheet baru dibuat
      var headers = [
        "TANGGAL ORDER", "TANGGAL KIRIM", "BADAN USAHA", "DEPO [WAREHOUSE]",
        "TUJUAN/DEALER", "ALAMAT KIRIM", "NAMA KOTA", "BRAND", "NOMOR SJ",
        "TOTAL NOMINAL SJ", "REFFNOTE", "QTY UNIT", "QTY KOLI", "BERAT",
        "KETENTUAN BIAYA KIRIM", "NAMA EKSPEDISI", "NO RESI AWB", "BIAYA KIRIM",
        "STATUS PEMBAYARAN", "STATUS PENGIRIMAN", "TANGGAL DITERIMA",
        "LEAD TIME PROSES", "LEAD TIME KIRIM", "LEAD TIME KESELURUHAN", "KET. ISI UNIT"
      ];
      sheet.appendRow(headers);
    }

    // Ambil daftar Nomor SJ yang sudah ada di sheet untuk mencegah duplikasi baris
    // Kolom I adalah kolom ke-9 (NOMOR SJ)
    var lastRow = sheet.getLastRow();
    var existingSj = {};

    if (lastRow > 1) {
      var values = sheet.getRange(2, 9, lastRow - 1, 1).getValues();
      for (var i = 0; i < values.length; i++) {
        var val = String(values[i][0]).trim();
        if (val) {
          existingSj[val] = true;
        }
      }
    }

    var rowsToInsert = [];
    for (var j = 0; j < rows.length; j++) {
      var row = rows[j];
      var sjNumber = String(row[8] || '').trim(); // Index 8 adalah NOMOR SJ

      // Lewati jika nomor SJ sudah ada di sheet
      if (!existingSj[sjNumber]) {
        rowsToInsert.push(row);
        existingSj[sjNumber] = true; // Tandai agar tidak duplikat dalam batch yang sama
      }
    }

    // Tulis baris baru sekaligus secara batch untuk kecepatan maksimal
    if (rowsToInsert.length > 0) {
      var startRow = sheet.getLastRow() + 1;
      sheet.getRange(startRow, 1, rowsToInsert.length, rowsToInsert[0].length).setValues(rowsToInsert);
    }

    return ContentService.createTextOutput(JSON.stringify({
      status: "success",
      sheet: sheetName,
      total_received: rows.length,
      inserted: rowsToInsert.length,
      skipped_duplicate: rows.length - rowsToInsert.length
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error",
      message: err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  } finally {
    lock.releaseLock();
  }
}

// Endpoint GET untuk uji konektivitas
function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: "active",
    message: "WMS Google Sheets Webhook is ready!"
  })).setMimeType(ContentService.MimeType.JSON);
}
