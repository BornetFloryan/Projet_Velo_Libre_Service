const paris = "Europe/Paris";

function asDate(value) {
  if (!value) return null;
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value) {
  const date = asDate(value);
  return date
    ? new Intl.DateTimeFormat("fr-FR", {
        dateStyle: "medium",
        timeZone: paris,
      }).format(date)
    : "—";
}

export function formatDateTime(value) {
  const date = asDate(value);
  return date
    ? new Intl.DateTimeFormat("fr-FR", {
        dateStyle: "medium",
        timeStyle: "short",
        timeZone: paris,
      }).format(date)
    : "—";
}
