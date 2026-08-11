export default function LocationPanel({ location, prosopa = [] }) {
  if (!location) return null;

  const chickens = prosopa.filter(p => p.type === 'bot');
  const humans   = prosopa.filter(p => p.type === 'user');

  return (
    <div style={s.panel}>
      <h2 style={s.title}>{location.name}</h2>
      <p style={s.description}>{location.description}</p>

      <div style={s.infoSection}>
        <span style={s.infoLabel}>Chickens</span>
        {chickens.length === 0 && (
          <p style={s.infoLine}>no chickens here</p>
        )}
        {chickens.length === 1 && (
          <p style={s.infoLine}>you see a chicken</p>
        )}
        {chickens.length > 1 && (
          <p style={s.infoLine}>there are chickens</p>
        )}
        {humans.length > 0 && (
          <p style={s.infoLine}>there are human users in the room</p>
        )}
      </div>
    </div>
  );
}

const s = {
  panel: {
    width: '100%',
    maxWidth: 480,
  },
  title: {
    fontSize: '1.5rem',
    fontWeight: 700,
    margin: '0 0 0.5rem',
    textAlign: 'left',
    color: '#1a1a1a',
  },
  description: {
    fontSize: '0.9rem',
    color: '#555',
    margin: '0 0 1.25rem',
    lineHeight: 1.6,
  },
  infoSection: {
    borderTop: '1px solid #e0e0e0',
    paddingTop: '0.75rem',
  },
  infoLabel: {
    display: 'block',
    fontSize: '0.7rem',
    letterSpacing: '0.12em',
    textTransform: 'uppercase',
    color: '#999',
    marginBottom: '0.35rem',
  },
  infoLine: {
    margin: '0.2rem 0',
    fontSize: '0.9rem',
    color: '#666',
  },
};
