:host {
  display: block;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  font-family: inherit;
  color: #f1f5f9;
}

*, *::before, *::after {
  box-sizing: border-box;
}

.tce-container {
  width: 100%;
  height: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  overflow: hidden;
}

.card {
  width: 100%;
  max-width: 360px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.card-header {
  margin-bottom: 12px;
}

.badge {
  display: inline-block;
  padding: 2px 8px;
  background: #0284c7;
  color: #fff;
  border-radius: 12px;
  font-size: 11px;
  text-transform: uppercase;
  font-weight: bold;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #38bdf8;
}

.description {
  margin: 0 0 16px 0;
  font-size: 0.85rem;
  color: #94a3b8;
  line-height: 1.4;
}

.counter-box {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  padding: 12px;
}

.counter-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 4px;
}

.counter-value {
  font-size: 2rem;
  font-weight: 700;
  color: #f8fafc;
  margin-bottom: 12px;
}

.button-group {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn {
  background: #334155;
  color: #f8fafc;
  border: none;
  border-radius: 6px;
  padding: 6px 14px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease-in-out;

  &:hover {
    background: #0284c7;
    transform: translateY(-1px);
  }

  &:active {
    transform: translateY(1px);
  }
}

canvas {
  max-width: 100%;
  max-height: 100%;
  border-radius: 6px;
}
