import { createRoot } from 'react-dom/client';
import { App } from './App';

const mount = document.getElementById('techbyit-smtp-app');
if (mount) createRoot(mount).render(<App slug={mount.dataset.page ?? 'techbyit-smtp'} />);
