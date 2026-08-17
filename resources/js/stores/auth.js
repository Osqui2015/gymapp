// ============================================================
// ⚠️  DEPRECATED: re-export desde auth.ts
// ============================================================
// Migrado a TypeScript (2026-08-17). Vite resuelve el import
// './auth' a .ts automáticamente. Este archivo queda como shim.
//
// Para eliminarlo:
//   Move-Item resources\js\stores\auth.js resources\js\stores\auth.js.bak
// ============================================================
export * from './auth.ts';
