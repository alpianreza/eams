<style>
  body {
    font-family: sans-serif;
    font-size: 10px;
    color: #111;
  }

  .title {
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 4px;
  }

  .subtitle {
    text-align: center;
    font-size: 11px;
    color: #4567b7;
    margin-bottom: 8px;
  }

  .code-line {
    text-align: center;
    font-size: 9px;
    margin-bottom: 12px;
    color: #555;
  }

  .instructions {
    border: 1px solid #cfd8ea;
    background: #f7faff;
    padding: 8px 10px;
    border-radius: 8px;
    margin-bottom: 12px;
    line-height: 1.5;
  }

  table {
    border-collapse: collapse;
    width: 100%;
  }

  .meta-table td {
    border: 1px solid #d8dfea;
    padding: 6px 8px;
    vertical-align: top;
  }

  .meta-label {
    font-size: 8px;
    color: #677489;
    text-transform: uppercase;
    margin-bottom: 2px;
  }

  .meta-value {
    font-size: 10px;
    font-weight: bold;
  }

  .section-title {
    font-size: 12px;
    font-weight: bold;
    margin-top: 14px;
    margin-bottom: 6px;
    color: #1d3557;
  }

  .question-card {
    border: 1px solid #d7deec;
    border-radius: 10px;
    padding: 8px 10px;
    margin-bottom: 8px;
    page-break-inside: avoid;
  }

  .question-label {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 6px;
    line-height: 1.45;
  }

  .option-table td {
    border: 0;
    padding: 2px 0;
    vertical-align: top;
  }

  .box {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 1px solid #111;
    text-align: center;
    line-height: 12px;
    font-size: 9px;
    font-weight: bold;
    margin-right: 6px;
  }

  .free-answer {
    border: 1px solid #ccd5e6;
    background: #fafcff;
    padding: 6px 8px;
    border-radius: 6px;
    line-height: 1.45;
  }

  .footer-note {
    margin-top: 16px;
    font-size: 9px;
    text-align: center;
    color: #5a6473;
  }
</style>

<div class="title"><?= esc($questionnaire['title']) ?></div>
<?php if (!empty($questionnaire['subtitle'])): ?>
  <div class="subtitle"><?= esc($questionnaire['subtitle']) ?></div>
<?php endif; ?>
<div class="code-line">Kode respon: <?= esc($response['response_code']) ?> | Dikirim: <?= esc($response['submitted_at'] ?: '-') ?></div>

<?php if (!empty($questionnaire['description'])): ?>
  <div class="instructions"><?= nl2br(esc($questionnaire['description'])) ?></div>
<?php endif; ?>

<?php
  $showName = (int) ($questionnaire['collect_name'] ?? 1) === 1;
  $showPhone = (int) ($questionnaire['collect_phone'] ?? 1) === 1;
  $showEmail = (int) ($questionnaire['collect_email'] ?? 1) === 1;
?>

<?php if ($showName || $showPhone || $showEmail): ?>
  <table class="meta-table">
    <tr>
      <?php if ($showName): ?>
        <td>
          <div class="meta-label">Nama</div>
          <div class="meta-value"><?= esc($response['respondent_name']) ?></div>
        </td>
      <?php endif; ?>
      <?php if ($showPhone): ?>
        <td>
          <div class="meta-label">No telepon</div>
          <div class="meta-value"><?= esc($response['phone'] ?: '-') ?></div>
        </td>
      <?php endif; ?>
      <?php if ($showEmail): ?>
        <td>
          <div class="meta-label">Email</div>
          <div class="meta-value"><?= esc($response['email'] ?: '-') ?></div>
        </td>
      <?php endif; ?>
    </tr>
  </table>
<?php endif; ?>

<?php foreach ($questionGroups as $section => $sectionQuestions): ?>
  <div class="section-title"><?= esc($section) ?></div>

  <?php foreach ($sectionQuestions as $question): ?>
    <?php $answerValue = (string) ($answersMap[$question['id']] ?? ''); ?>
    <div class="question-card">
      <div class="question-label">
        <?= (int) ($question['display_order'] ?? 0) ?>.
        <?= esc($question['question_text']) ?>
      </div>

      <?php if (in_array($question['answer_type'], ['scale_5', 'scale_10'], true)): ?>
        <?php
          $scaleLow = trim((string) ($question['scale_low_label'] ?? ''));
          $scaleHigh = trim((string) ($question['scale_high_label'] ?? ''));
        ?>
        <table class="option-table" style="margin-bottom:4px;">
          <tr>
            <td style="font-size:9px;color:#5f6e84;"><?= esc($scaleLow !== '' ? $scaleLow : '-') ?></td>
            <td style="text-align:right;font-size:9px;color:#5f6e84;"><?= esc($scaleHigh !== '' ? $scaleHigh : '-') ?></td>
          </tr>
        </table>
        <table class="option-table">
          <tr>
            <?php foreach ($question['options'] as $option): ?>
              <td style="text-align:center;padding-right:6px;">
                <div style="font-size:9px;font-weight:bold;margin-bottom:2px;"><?= esc($option) ?></div>
                <span class="box"><?= $answerValue === $option ? '&#10003;' : '' ?></span>
              </td>
            <?php endforeach; ?>
          </tr>
        </table>
      <?php elseif (!empty($question['options'])): ?>
        <table class="option-table">
          <?php foreach ($question['options'] as $option): ?>
            <tr>
              <td style="width:18px;"><span class="box"><?= $answerValue === $option ? '&#10003;' : '' ?></span></td>
              <td><?= esc($option) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <div class="free-answer"><?= nl2br(esc($answerValue !== '' ? $answerValue : '-')) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (!empty($questionnaire['footer_note'])): ?>
  <div class="footer-note"><?= esc($questionnaire['footer_note']) ?></div>
<?php endif; ?>
