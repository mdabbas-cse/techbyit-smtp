import { ProviderList } from '../components/ProviderList';
import { ProviderSettings } from '../components/ProviderSettings';
import { useProviders } from '../state/useProviders';

export function ProvidersPage() {
  const state = useProviders();
  return <section>
    <p>Choose a mailer to configure. Custom SMTP supports sending in this phase.</p>
    {state.loading && <p role="status">Loading providers…</p>}
    {state.error && <div className="notice notice-error" role="alert"><p>{state.error}</p></div>}
    <ProviderList providers={state.providers} selected={state.selected} onSelect={state.setSelected} />
    {state.selected && !state.metadata && !state.error && <p role="status">Loading provider settings…</p>}
    {state.metadata && state.settings && state.metadata.id === state.selected && <ProviderSettings key={state.selected}
      provider={state.metadata} settings={state.settings} onUpdate={(settings) => {
        state.setSettings(settings);
        state.refresh().catch(() => state.setError('Could not refresh provider status.'));
      }} />}
  </section>;
}
