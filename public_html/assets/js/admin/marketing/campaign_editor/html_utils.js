export function insertIntoTextarea(textarea, html) {
  const ta = textarea;
  const start = ta.selectionStart ?? ta.value.length;
  const end = ta.selectionEnd ?? ta.value.length;
  const before = ta.value.slice(0, start);
  const after = ta.value.slice(end);
  ta.value = before + html + after;
  const pos = (before + html).length;
  ta.setSelectionRange(pos, pos);
  ta.focus();
}
