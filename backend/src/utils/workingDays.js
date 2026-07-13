// PRD 9.1.2/10.3: SLA duration is in working days, excluding weekends and an
// admin-configured Ghana public holidays calendar. The holidays calendar
// admin UI isn't built in this v1 slice, so this only skips weekends - swap
// in a real holiday list once that config exists.
export function addWorkingDays(startDate, days) {
  const date = new Date(startDate);
  let remaining = days;
  while (remaining > 0) {
    date.setDate(date.getDate() + 1);
    const day = date.getDay();
    if (day !== 0 && day !== 6) remaining -= 1;
  }
  return date;
}
